<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Services\AuditService;
use Exception;

class StockService
{
    /**
     * Increase stock (purchase, transfer in, opening)
     */
    public function increase(
    Warehouse $warehouse,
    int $productId,
    int $quantity,
    string $source,
    int $sourceId
): void {
    if ($quantity <= 0) {
        throw new \InvalidArgumentException('Stock increase quantity must be positive.');
    }

    // HARD SOURCE VALIDATION
    if (!in_array($source, [
        'purchase_receipt',
        'warehouse_fulfillment',
    ], true)) {
        throw new \DomainException(
            'Stock increase is only allowed via warehouse receipt or fulfillment.'
        );
    }

    DB::transaction(function () use ($warehouse, $productId, $quantity, $source, $sourceId) {

        $stock = WarehouseStock::firstOrCreate(
            [
                'warehouse_id' => $warehouse->id,
                'product_id' => $productId,
            ],
            [
                'quantity' => 0,
            ]
        );

        $stock->increment('quantity', $quantity);

        StockMovement::create([
    'business_id'    => $warehouse->business_id,
    'warehouse_id'   => $warehouse->id,
    'product_id'     => $productId,
    'quantity'       => $quantity,
    'type'           => 'sale', // Change 'warehouse_fulfillment' to 'sale'
    'reference_type' => 'warehouse_fulfillments',
    'reference_id'   => $referenceId,
    'created_by'     => auth()->id(),
]);
    });
}


    /**
     * Decrease stock (sale, transfer out)
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
                    'quantity' => DB::raw("quantity - {$quantity}"),
                    'updated_at' => now(),
                ]);

            DB::table('stock_movements')->insert([
                'business_id'   => $businessId,
                'warehouse_id'  => $warehouseId,
                'product_id'    => $productId,
                'type'          => $type,
                'quantity'      => -$quantity,
                'reference_type'=> $referenceType,
                'reference_id'  => $referenceId,
                'created_by'    => $userId,
                'created_at'    => now(),
                'updated_at'    => now(),
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
