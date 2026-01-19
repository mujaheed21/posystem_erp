<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Business;
use App\Models\Product;

class ValuationService
{
    /**
     * Calculate and consume the Cost of Goods Sold (COGS).
     */
    public function consumeStockAndGetCogs(int $businessId, int $warehouseId, int $productId, float $qtyToConsume): float
    {
        $business = Business::find($businessId);
        $method = $business->valuation_method ?? 'fifo'; // Default to FIFO

        if ($method === 'fifo') {
            return $this->processFifo($warehouseId, $productId, $qtyToConsume);
        }

        return $this->processWeightedAverage($warehouseId, $productId, $qtyToConsume);
    }

    /**
     * FIFO: First-In, First-Out
     * Deducts quantity from the oldest batches first.
     */
    protected function processFifo(int $warehouseId, int $productId, float $qtyToConsume): float
    {
        $batches = DB::table('stock_batches')
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->where('quantity_remaining', '>', 0)
            ->orderBy('received_at', 'asc')
            ->lockForUpdate() // Prevent race conditions
            ->get();

        $totalCogs = 0;
        $remainingToProcess = $qtyToConsume;

        foreach ($batches as $batch) {
            if ($remainingToProcess <= 0) break;

            $take = min($batch->quantity_remaining, $remainingToProcess);
            $totalCogs += ($take * $batch->unit_cost);

            // Update the batch
            DB::table('stock_batches')
                ->where('id', $batch->id)
                ->update([
                    'quantity_remaining' => $batch->quantity_remaining - $take,
                    'updated_at' => now()
                ]);

            $remainingToProcess -= $take;
        }

        if ($remainingToProcess > 0) {
            // This handles cases where stock exists in warehouse_stock 
            // but batches were somehow missing (Fallback to last cost)
            $lastCost = DB::table('products')->where('id', $productId)->value('cost_price');
            $totalCogs += ($remainingToProcess * $lastCost);
        }

        return $totalCogs;
    }

    /**
     * Weighted Average:
     * Deducts quantity from batches proportionally or simply reduces the pool
     * while using the current average cost of all remaining units.
     */
    protected function processWeightedAverage(int $warehouseId, int $productId, float $qtyToConsume): float
    {
        // 1. Calculate the current average cost of all available batches
        $totals = DB::table('stock_batches')
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->where('quantity_remaining', '>', 0)
            ->selectRaw('SUM(quantity_remaining * unit_cost) as total_value, SUM(quantity_remaining) as total_qty')
            ->first();

        $averageCost = $totals->total_qty > 0 ? ($totals->total_value / $totals->total_qty) : 0;
        $totalCogs = $qtyToConsume * $averageCost;

        // 2. We still need to reduce quantity from batches for FIFO-compatibility 
        // (Just reducing from oldest batches to clear the units)
        $this->processFifo($warehouseId, $productId, $qtyToConsume); 

        return $totalCogs;
    }
}