<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class StockAlertService
{
    /**
     * Get products where current stock is at or below the minimum threshold.
     */
    public function getLowStockAlerts(int $businessId)
    {
        return DB::table('stock_thresholds')
            ->join('products', 'stock_thresholds.product_id', '=', 'products.id')
            ->join('warehouses', 'stock_thresholds.warehouse_id', '=', 'warehouses.id')
            // Subquery to get current totals from our FIFO batches
            ->join(DB::raw('(SELECT warehouse_id, product_id, SUM(quantity_remaining) as current_stock 
                             FROM stock_batches GROUP BY warehouse_id, product_id) as inventory'), 
                   function($join) {
                       $join->on('stock_thresholds.warehouse_id', '=', 'inventory.warehouse_id')
                            ->on('stock_thresholds.product_id', '=', 'inventory.product_id');
                   })
            ->where('stock_thresholds.business_id', $businessId)
            ->whereRaw('inventory.current_stock <= stock_thresholds.min_level')
            ->select(
                'warehouses.name as warehouse_name',
                'products.name as product_name',
                'inventory.current_stock',
                'stock_thresholds.min_level',
                'stock_thresholds.reorder_qty'
            )
            ->get();
    }
}