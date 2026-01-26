<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Business;
use App\Models\Product;
use Illuminate\Support\Collection;

class ValuationService
{
    /**
     * Get the financial value of stock per location.
     */
    public function getLocationValuation(int $businessId): Collection
    {
        return DB::table('stock_batches')
            ->join('warehouses', 'stock_batches.warehouse_id', '=', 'warehouses.id')
            ->select(
                'warehouses.id as warehouse_id',
                'warehouses.name as location_name',
                DB::raw('COALESCE(SUM(quantity_remaining * unit_cost), 0) as total_value'),
                DB::raw('COUNT(DISTINCT product_id) as unique_products_count')
            )
            ->where('stock_batches.business_id', $businessId)
            ->where('quantity_remaining', '>', 0)
            ->groupBy('warehouses.id', 'warehouses.name')
            ->get();
    }

    /**
     * Get the value of stock currently "on the road" (In-Transit).
     * Updated to return the array structure expected by the Controller and Tests.
     */
    public function getInTransitValuation(int $businessId): array
    {
        $result = DB::table('stock_transfer_items')
            ->join('stock_transfers', 'stock_transfer_items.stock_transfer_id', '=', 'stock_transfers.id')
            ->join('stock_batches', 'stock_transfer_items.stock_batch_id', '=', 'stock_batches.id')
            ->where('stock_transfers.business_id', $businessId)
            ->where('stock_transfers.status', 'in_transit')
            ->select(DB::raw('COALESCE(SUM(stock_transfer_items.quantity * stock_batches.unit_cost), 0) as transit_value'))
            ->first();

        return [
            'transit_value' => (float) ($result->transit_value ?? 0)
        ];
    }

    /**
     * Calculate and consume the Cost of Goods Sold (COGS).
     */
    public function consumeStockAndGetCogs(int $businessId, int $warehouseId, int $productId, float $qtyToConsume): float
    {
        $business = Business::find($businessId);
        $method = $business->valuation_method ?? 'fifo';

        if ($method === 'fifo') {
            return $this->processFifo($warehouseId, $productId, $qtyToConsume);
        }

        return $this->processWeightedAverage($warehouseId, $productId, $qtyToConsume);
    }

    /**
     * FIFO: First-In, First-Out
     */
    protected function processFifo(int $warehouseId, int $productId, float $qtyToConsume): float
    {
        $batches = DB::table('stock_batches')
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->where('quantity_remaining', '>', 0)
            ->orderBy('received_at', 'asc')
            ->lockForUpdate()
            ->get();

        $totalCogs = 0;
        $remainingToProcess = $qtyToConsume;

        foreach ($batches as $batch) {
            if ($remainingToProcess <= 0) break;

            $take = min($batch->quantity_remaining, $remainingToProcess);
            $totalCogs += ($take * $batch->unit_cost);

            DB::table('stock_batches')
                ->where('id', $batch->id)
                ->update([
                    'quantity_remaining' => $batch->quantity_remaining - $take,
                    'updated_at' => now()
                ]);

            $remainingToProcess -= $take;
        }

        if ($remainingToProcess > 0) {
            $lastCost = DB::table('products')->where('id', $productId)->value('cost_price');
            $totalCogs += ($remainingToProcess * ($lastCost ?? 0));
        }

        return $totalCogs;
    }

    /**
     * Weighted Average logic
     */
    protected function processWeightedAverage(int $warehouseId, int $productId, float $qtyToConsume): float
    {
        $totals = DB::table('stock_batches')
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->where('quantity_remaining', '>', 0)
            ->selectRaw('SUM(quantity_remaining * unit_cost) as total_value, SUM(quantity_remaining) as total_qty')
            ->first();

        $averageCost = ($totals && $totals->total_qty > 0) ? ($totals->total_value / $totals->total_qty) : 0;
        $totalCogs = $qtyToConsume * $averageCost;

        $this->processFifo($warehouseId, $productId, $qtyToConsume); 

        return $totalCogs;
    }
}