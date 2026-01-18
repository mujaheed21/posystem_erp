<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Services\AuditService;
use App\Models\Warehouse;
use Exception;
use RuntimeException;

class StockService
{
    /**
     * Reserve stock for a sale (INTENT ONLY).
     */
    public function reserve(
        int $warehouseId,
        array $items
    ): void {
        DB::transaction(function () use ($warehouseId, $items) {
            foreach ($items as $item) {
                $stock = DB::table('warehouse_stock')
                    ->where('warehouse_id', $warehouseId)
                    ->where('product_id', $item['product_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$stock) {
                    throw new RuntimeException('Stock record not found.');
                }

                $available = $stock->quantity - $stock->reserved_quantity;

                if ($available < $item['quantity']) {
                    throw new RuntimeException('Insufficient stock.');
                }

                DB::table('warehouse_stock')
                    ->where('warehouse_id', $warehouseId)
                    ->where('product_id', $item['product_id'])
                    ->update([
                        'reserved_quantity' => DB::raw(
                            "reserved_quantity + {$item['quantity']}"
                        ),
                        'updated_at' => now(),
                    ]);
            }
        });
    }

    /**
     * Increase stock (purchase, transfer in, opening).
     */
    public function increase(
        Warehouse $warehouse,
        int $productId,
        int $quantity,
        string $source,
        int $sourceId
    ): void {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException(
                'Stock increase quantity must be positive.'
            );
        }

        // HARD SOURCE VALIDATION - UPDATED TO ALLOW 'purchase'
        if (!in_array($source, [
            'purchase',
            'purchase_receipt',
            'warehouse_fulfillment',
            'sale_return',
        ], true)) {
            throw new \DomainException(
                'Stock increase is only allowed via purchase, warehouse receipt or fulfillment.'
            );
        }

        DB::transaction(function () use (
            $warehouse,
            $productId,
            $quantity,
            $source,
            $sourceId
        ) {
            $stock = DB::table('warehouse_stock')
                ->where('warehouse_id', $warehouse->id)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            if (!$stock) {
                DB::table('warehouse_stock')->insert([
                    'warehouse_id' => $warehouse->id,
                    'product_id'   => $productId,
                    'quantity'     => 0,
                    'reserved_quantity' => 0,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }

            DB::table('warehouse_stock')
                ->where('warehouse_id', $warehouse->id)
                ->where('product_id', $productId)
                ->update([
                    'quantity'   => DB::raw("quantity + {$quantity}"),
                    'updated_at' => now(),
                ]);

            DB::table('stock_movements')->insert([
                'business_id'    => $warehouse->business_id,
                'warehouse_id'   => $warehouse->id,
                'product_id'     => $productId,
                'quantity'       => $quantity,
                'type'           => 'purchase',
                'reference_type' => $source,
                'reference_id'   => $sourceId,
                'created_by'     => auth()->id() ?? 1,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            AuditService::log(
                'stock_increase',
                'inventory',
                'products',
                $productId,
                [
                    'warehouse_id' => $warehouse->id,
                    'quantity'     => $quantity,
                    'source'       => $source,
                ]
            );
        });
    }

    /**
     * Decrease stock (sale, transfer out).
     */
    public static function decrease(
        int $businessId,
        int $warehouseId,
        int $productId,
        float $quantity,
        string $type,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $userId = null
    ): void {
        DB::transaction(function () use (
            $businessId,
            $warehouseId,
            $productId,
            $quantity,
            $type,
            $referenceType,
            $referenceId,
            $userId
        ) {
            $stock = DB::table('warehouse_stock')
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            if (!$stock || $stock->quantity < $quantity) {
                throw new Exception('Insufficient stock');
            }

            DB::table('warehouse_stock')
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->update([
                    'quantity'   => DB::raw("quantity - {$quantity}"),
                    'updated_at' => now(),
                ]);

            DB::table('stock_movements')->insert([
                'business_id'    => $businessId,
                'warehouse_id'   => $warehouseId,
                'product_id'     => $productId,
                'type'           => $type,
                'quantity'       => -$quantity,
                'reference_type' => $referenceType,
                'reference_id'   => $referenceId,
                'created_by'     => $userId,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            AuditService::log(
                'stock_decrease',
                'inventory',
                'products',
                $productId,
                [
                    'warehouse_id' => $warehouseId,
                    'quantity'     => $quantity,
                    'type'         => $type,
                ]
            );
        });
    }
}