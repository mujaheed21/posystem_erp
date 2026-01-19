<?php

namespace App\Services;

use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use Illuminate\Support\Facades\DB;

class StockTransferService
{
    public function dispatch(array $data)
{
    return DB::transaction(function () use ($data) {
        $transfer = StockTransfer::create([
            'business_id'       => $data['business_id'],
            'from_warehouse_id' => $data['from_warehouse_id'],
            'to_warehouse_id'   => $data['to_warehouse_id'],
            'transfer_number'   => 'TRF-' . now()->timestamp,
            'status'            => 'in_transit', 
            'created_by'        => $data['user_id'],
            'verification_token' => \Illuminate\Support\Str::random(40), // Generate here
        ]);

        foreach ($data['items'] as $item) {
            $this->processFIFODispatch($transfer, $item);
        }

        return $transfer;
    });
}

public function fulfillByToken(string $token, int $userId)
{
    $transfer = StockTransfer::where('verification_token', $token)
        ->where('status', 'in_transit')
        ->first();

    if (!$transfer) {
        throw new \Exception("Invalid token, or this transfer has already been fulfilled.");
    }

    return $this->receive((int) $transfer->id, $userId);
}

    public function receive(int $transferId, int $userId)
    {
        return DB::transaction(function () use ($transferId, $userId) {
            $transfer = StockTransfer::with('items')->findOrFail($transferId);

            if ($transfer->status !== 'in_transit') {
                throw new \Exception("Only in-transit transfers can be received.");
            }

            foreach ($transfer->items as $item) {
                $sourceBatch = StockBatch::findOrFail($item->stock_batch_id);

                $newBatch = StockBatch::create([
                    'business_id'        => $transfer->business_id,
                    'warehouse_id'       => $transfer->to_warehouse_id,
                    'product_id'         => $item->product_id,
                    'purchase_id'        => $sourceBatch->purchase_id,
                    'quantity_received'  => $item->quantity,
                    'quantity_remaining' => $item->quantity,
                    'unit_cost'          => $sourceBatch->unit_cost,
                    'received_at'        => now(),
                ]);

                StockMovement::create([
                    'business_id'    => $transfer->business_id,
                    'warehouse_id'   => $transfer->to_warehouse_id,
                    'product_id'     => $item->product_id,
                    'type'           => 'transfer_in',
                    'quantity'       => $item->quantity,
                    'reference_type' => StockBatch::class, 
                    'reference_id'   => $newBatch->id,
                    'created_by'     => $userId
                ]);
            }

            // REMOVED 'received_at' and 'received_by' to match your DESCRIBE output
            $transfer->update([
                'status' => 'completed',
            ]);

            return $transfer;
        });
    }

    protected function processFIFODispatch($transfer, $item)
    {
        $qtyToMove = $item['quantity'];
        $batches = StockBatch::where('product_id', $item['product_id'])
            ->where('warehouse_id', $transfer->from_warehouse_id)
            ->where('quantity_remaining', '>', 0)
            ->orderBy('received_at', 'asc')
            ->get();

        foreach ($batches as $batch) {
            if ($qtyToMove <= 0) break;
            $take = min($batch->quantity_remaining, $qtyToMove);

            $transferItem = StockTransferItem::create([
                'stock_transfer_id' => $transfer->id,
                'product_id'        => $item['product_id'],
                'stock_batch_id'    => $batch->id,
                'quantity'          => $take
            ]);

            $batch->decrement('quantity_remaining', $take);

            StockMovement::create([
                'business_id'    => $transfer->business_id,
                'warehouse_id'   => $transfer->from_warehouse_id,
                'product_id'     => $item['product_id'],
                'type'           => 'transfer_out',
                'quantity'       => $take,
                'reference_type' => StockBatch::class,
                'reference_id'   => $batch->id,
                'created_by'     => $transfer->created_by
            ]);

            $qtyToMove -= $take;
        }

        if ($qtyToMove > 0) {
            throw new \Exception("Insufficient stock for product ID: {$item['product_id']}");
        }
    }
    /**
 * Verifies the QR token and triggers the receipt of stock.
 */
} 