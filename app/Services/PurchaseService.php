<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\StockService;
use App\Services\AuditService;

class PurchaseService
{
    public static function create(array $data)
    {
        return DB::transaction(function () use ($data) {

            $user = Auth::user();

            // 1. Create purchase header
            $purchaseId = DB::table('purchases')->insertGetId([
                'business_id'    => $user->business_id,
                'warehouse_id'   => $data['warehouse_id'],
                'supplier_id'    => $data['supplier_id'],
                'purchase_number'=> self::generatePurchaseNumber(),
                'subtotal'       => $data['subtotal'],
                'tax'            => $data['tax'] ?? 0,
                'total'          => $data['total'],
                'status'         => 'received',
                'created_by'     => $user->id,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            // 2. Create purchase items & increase stock
            foreach ($data['items'] as $item) {

                DB::table('purchase_items')->insert([
                    'purchase_id' => $purchaseId,
                    'product_id'  => $item['product_id'],
                    'quantity'    => $item['quantity'],
                    'unit_cost'   => $item['unit_cost'],
                    'total_cost'  => $item['total_cost'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);

                StockService::increase(
                    $user->business_id,
                    $data['warehouse_id'],
                    $item['product_id'],
                    $item['quantity'],
                    'purchase',
                    'purchases',
                    $purchaseId,
                    $user->id
                );
            }

            // 3. Audit
            AuditService::log(
                'purchase_received',
                'procurement',
                'purchases',
                $purchaseId,
                [
                    'warehouse_id' => $data['warehouse_id'],
                    'total'        => $data['total'],
                ]
            );

            return $purchaseId;
        });
    }

    private static function generatePurchaseNumber(): string
    {
        return 'PUR-' . now()->format('YmdHis') . '-' . random_int(100, 999);
    }
}
