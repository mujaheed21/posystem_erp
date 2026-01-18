<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Throwable;
use App\Models\OfflineFulfillmentPending;
use Illuminate\Validation\ValidationException;
use App\Models\Warehouse;
use App\Services\FulfillmentStateMachine;
use App\Services\OfflineFulfillmentStateMachine;
use App\Models\WarehouseFulfillment;
use App\Services\StockService;

class FulfillmentService
{
    /**
     * Fulfill a sale using a single-use fulfillment token.
     * CONTINUITY: Follows the lifecycle pending → approved → released → reconciled.
     */
    public static function fulfill(string $plainToken): void
    {
        try {
            DB::transaction(function () use ($plainToken) {
                $user = Auth::user();
                $tokenHash = hash('sha256', $plainToken);

                $token = DB::table('fulfillment_tokens')
                    ->where('token_hash', $tokenHash)
                    ->lockForUpdate()
                    ->first();

                if (!$token) {
                    throw new RuntimeException('invalid_token');
                }

                if ($token->used) {
                    throw new RuntimeException('token_used');
                }

                if (now()->greaterThan($token->expires_at)) {
                    throw new RuntimeException('token_expired');
                }

                $sale = DB::table('sales')->where('id', $token->sale_id)->first();
                if (!$sale) {
                    throw new RuntimeException('invalid_sale');
                }

                $items = DB::table('sale_items')
                    ->where('sale_id', $token->sale_id)
                    ->orderBy('product_id', 'asc')
                    ->get(['product_id', 'quantity']);

                if ($items->isEmpty()) {
                    throw new RuntimeException('empty_sale');
                }

                // Verify item integrity
                $dataToHash = $items->map(fn ($i) => [
                    'product_id' => (int) $i->product_id,
                    'quantity'   => (string) number_format((float) $i->quantity, 2, '.', ''),
                ])->values()->all();

                $currentItemsHash = hash(
                    'sha256',
                    json_encode($dataToHash, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                );

                if ($currentItemsHash !== $token->items_hash) {
                    throw new RuntimeException('sale_items_modified');
                }

                // Invalidate token
                DB::table('fulfillment_tokens')
                    ->where('id', $token->id)
                    ->update([
                        'used'       => true,
                        'used_at'    => now(),
                        'updated_at' => now(),
                    ]);

                // Create fulfillment record
                $fulfillmentId = DB::table('warehouse_fulfillments')->insertGetId([
                    'sale_id'      => $token->sale_id,
                    'warehouse_id' => $token->warehouse_id,
                    'verified_by'  => $user?->id,
                    'verified_at'  => now(),
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);

                $fulfillment = WarehouseFulfillment::findOrFail($fulfillmentId);

                // Lifecycle transitions
                $fulfillment = FulfillmentStateMachine::transition($fulfillment, 'approved');
                $fulfillment = FulfillmentStateMachine::transition($fulfillment, 'released');


                // Terminal transition
                FulfillmentStateMachine::transition($fulfillment, 'reconciled');
            });

        } catch (Throwable $e) {
            if (isset($fulfillment)) {
                FulfillmentStateMachine::conflict($fulfillment, $e->getMessage());
            }
            throw $e;
        }
    }

    /**
     * Commit stock to the ledger.
     * CONTINUITY: Uses ledger-based idempotency.
     */
    public static function commitStockOnRelease(int $fulfillmentId): void
    {
        DB::transaction(function () use ($fulfillmentId) {
            $fulfillment = DB::table('warehouse_fulfillments')
                ->where('id', $fulfillmentId)
                ->lockForUpdate()
                ->first();

            // Guard: Allow commitment in released or reconciled state
            if (!$fulfillment || !in_array($fulfillment->state, ['released', 'reconciled'])) {
                return;
            }

            /**
             * 🛑 LEDGER IDEMPOTENCY GUARD
             * Source of truth is the stock_movements table.
             */
            $alreadyCommitted = DB::table('stock_movements')
                ->where('reference_type', 'warehouse_fulfillment')
                ->where('reference_id', $fulfillmentId)
                ->exists();

            if ($alreadyCommitted) {
                return;
            }

            $sale = DB::table('sales')
                ->where('id', $fulfillment->sale_id)
                ->first();

            $items = DB::table('sale_items')
                ->where('sale_id', $sale->id)
                ->get();

            foreach ($items as $item) {
                $stock = DB::table('warehouse_stock')
                    ->where('warehouse_id', $sale->warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                if (!$stock) {
                    throw new RuntimeException('Stock record missing during commit.');
                }

                // ✅ Commit stock mutation
                DB::table('warehouse_stock')
                    ->where('warehouse_id', $sale->warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->update([
                        'reserved_quantity' => DB::raw("GREATEST(0, reserved_quantity - {$item->quantity})"),
                        'quantity'          => DB::raw("quantity - {$item->quantity}"),
                        'updated_at'        => now(),
                    ]);

                // ✅ Ledger Entry
                DB::table('stock_movements')->insert([
                    'business_id'    => $sale->business_id,
                    'warehouse_id'   => $sale->warehouse_id,
                    'product_id'     => $item->product_id,
                    'type'           => 'sale',
                    'quantity'       => -$item->quantity,
                    'reference_type' => 'warehouse_fulfillment',
                    'reference_id'   => $fulfillmentId,
                    'created_by'     => $fulfillment->verified_by,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        });
    }

    /**
     * Fulfill an approved offline fulfillment pending record.
     */
    public function fulfillOffline(OfflineFulfillmentPending $pending): void
    {
        try {
            $pending->refresh();
            $payload = $pending->payload;

            if (!isset($payload['items']) || empty($payload['items'])) {
                throw ValidationException::withMessages([
                    'payload' => 'Offline payload missing items.',
                ]);
            }

            $warehouse = Warehouse::query()
                ->select('id', 'business_id')
                ->findOrFail($pending->warehouse_id);

            foreach ($payload['items'] as $item) {
                $alreadyDeducted = DB::table('stock_movements')
                    ->where('reference_type', 'offline_fulfillment_pendings')
                    ->where('reference_id', $pending->id)
                    ->where('product_id', $item['product_id'])
                    ->exists();

                if ($alreadyDeducted) {
                    continue;
                }

                StockService::decrease(
                    (int) $warehouse->business_id,
                    (int) $warehouse->id,
                    (int) $item['product_id'],
                    (float) $item['quantity'],
                    'sale',
                    'offline_fulfillment_pendings',
                    $pending->id,
                    Auth::id()
                );
            }
        } catch (Throwable $e) {
            OfflineFulfillmentStateMachine::conflict($pending, $e->getMessage());
            throw $e;
        }
    }
}