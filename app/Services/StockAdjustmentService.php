<?php

namespace App\Services;

use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use Illuminate\Support\Facades\DB;
use Exception;

class StockAdjustmentService
{
    public function adjust(array $data): StockAdjustment
    {
        return DB::transaction(function () use ($data) {
            $adjustment = StockAdjustment::create([
                'business_id'       => $data['business_id'],
                'warehouse_id'      => $data['warehouse_id'],
                'adjustment_number' => 'ADJ-' . now()->timestamp,
                'type'              => $data['type'], 
                'notes'             => $data['notes'] ?? null,
                'created_by'        => $data['user_id'],
            ]);

            foreach ($data['items'] as $item) {
                $this->applyFIFOAdjustment($adjustment, $item);
            }

            return $adjustment;
        });
    }

    protected function applyFIFOAdjustment(StockAdjustment $adjustment, array $item): void
    {
        $qtyToAdjust = $item['quantity'];
        
        $batches = StockBatch::where('product_id', $item['product_id'])
            ->where('warehouse_id', $adjustment->warehouse_id)
            ->where('quantity_remaining', '>', 0)
            ->orderBy('received_at', 'asc')
            ->get();

        foreach ($batches as $batch) {
            if ($qtyToAdjust <= 0) break;
            
            $take = min($batch->quantity_remaining, $qtyToAdjust);

            StockAdjustmentItem::create([
                'stock_adjustment_id' => $adjustment->id,
                'product_id'          => $item['product_id'],
                'stock_batch_id'      => $batch->id,
                'quantity'            => $take
            ]);

            $batch->decrement('quantity_remaining', $take);

            StockMovement::create([
                'business_id'    => $adjustment->business_id,
                'warehouse_id'   => $adjustment->warehouse_id,
                'product_id'     => $item['product_id'],
                'type'           => 'adjustment', // Matches your ENUM exactly
                'quantity'       => $take,
                'reference_type' => StockBatch::class,
                'reference_id'   => $batch->id,
                'created_by'     => $adjustment->created_by
            ]);

            $qtyToAdjust -= $take;
        }

        if ($qtyToAdjust > 0) {
            throw new Exception("Insufficient stock to perform adjustment for Product ID: {$item['product_id']}.");
        }
    }
}