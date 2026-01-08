<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\StockService;
use App\Services\AuditService;
use Exception;

class TransferService
{
    public static function create(array $data)
    {
        return DB::transaction(function () use ($data) {

            $user = Auth::user();

            if ($data['from_warehouse_id'] === $data['to_warehouse_id']) {
                throw new Exception('Source and destination warehouses must be different');
            }

            // 1. Create transfer header
            $transferId = DB::table('stock_transfers')->insertGetId([
                'business_id'       => $user->business_id,
                'from_warehouse_id' => $data['from_warehouse_id'],
                'to_warehouse_id'   => $data['to_warehouse_id'],
                'transfer_number'   => self::generateTransferNumber(),
                'status'            => 'completed',
                'created_by'        => $user->id,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            // 2. Process transfer items
            foreach ($data['items'] as $item) {

                DB::table('stock_transfer_items')->insert([
                    'stock_transfer_id' => $transferId,
                    'product_id'        => $item['product_id'],
                    'quantity'          => $item['quantity'],
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);

                // Decrease stock from source warehouse
                StockService::decrease(
                    $user->business_id,
                    $data['from_warehouse_id'],
                    $item['product_id'],
                    $item['quantity'],
                    'transfer_out',
                    'stock_transfers',
                    $transferId,
                    $user->id
                );

                // Increase stock in destination warehouse
                StockService::increase(
                    $user->business_id,
                    $data['to_warehouse_id'],
                    $item['product_id'],
                    $item['quantity'],
                    'transfer_in',
                    'stock_transfers',
                    $transferId,
                    $user->id
                );
            }

            // 3. Audit
            AuditService::log(
                'stock_transferred',
                'warehouse',
                'stock_transfers',
                $transferId,
                [
                    'from_warehouse' => $data['from_warehouse_id'],
                    'to_warehouse'   => $data['to_warehouse_id'],
                ]
            );

            return $transferId;
        });
    }

    private static function generateTransferNumber(): string
    {
        return 'TRF-' . now()->format('YmdHis') . '-' . random_int(100, 999);
    }
}
