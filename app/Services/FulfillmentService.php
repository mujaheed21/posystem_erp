<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Throwable;

class FulfillmentService
{
    /**
     * Fulfill a sale using a single-use fulfillment token.
     *
     * @throws RuntimeException
     */
    public static function fulfill(string $plainToken): void
    {
        try {
            DB::transaction(function () use ($plainToken) {

                $user = Auth::user();
                $tokenHash = hash('sha256', $plainToken);

                // Lock token row to guarantee single-use
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

                $businessId = (int) $sale->business_id;

                // Load sale items deterministically
                $items = DB::table('sale_items')
                    ->where('sale_id', $token->sale_id)
                    ->orderBy('product_id', 'asc')
                    ->get(['product_id', 'quantity']);

                if ($items->isEmpty()) {
                    throw new RuntimeException('empty_sale');
                }

                // Rebuild hash to detect tampering
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

                // Mark token as used FIRST (critical invariant)
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

                // Decrease stock (mapped to valid ENUM type: 'sale')
                foreach ($items as $item) {
                    StockService::decrease(
                        $businessId,
                        (int) $token->warehouse_id,
                        (int) $item->product_id,
                        (float) $item->quantity,
                        'sale', // ✅ VALID ENUM VALUE
                        'warehouse_fulfillments',
                        $fulfillmentId,
                        $user?->id
                    );
                }
            });
        } catch (RuntimeException $e) {
            // 🔴 REQUIRED: rethrow business exceptions so controller/tests can react
            throw $e;
        } catch (Throwable $e) {
            // Allow unexpected errors to surface (500)
            throw $e;
        }
    }
}
