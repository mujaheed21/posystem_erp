<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\StockService;
use App\Services\AuditService;
use Exception;

class SaleService
{
    public static function create(array $data)
    {
        return DB::transaction(function () use ($data) {

            $user = Auth::user();

            // 1. Create sale header
            $saleId = DB::table('sales')->insertGetId([
                'business_id'          => $user->business_id,
                'business_location_id' => $user->business_location_id,
                'warehouse_id'         => $data['warehouse_id'],
                'sale_number'          => self::generateSaleNumber(),
                'subtotal'             => $data['subtotal'],
                'discount'             => $data['discount'] ?? 0,
                'tax'                  => $data['tax'] ?? 0,
                'total'                => $data['total'],
                'status'               => 'completed',
                'created_by'           => $user->id,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);

            // 2. Create sale items & reserve stock
            foreach ($data['items'] as $item) {

                DB::table('sale_items')->insert([
                    'sale_id'     => $saleId,
                    'product_id'  => $item['product_id'],
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['unit_price'],
                    'total_price' => $item['total_price'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);

                // Reserve stock immediately (decrease available quantity)
                StockService::decrease(
                    $user->business_id,
                    $data['warehouse_id'],
                    $item['product_id'],
                    $item['quantity'],
                    'sale',
                    'sales',
                    $saleId,
                    $user->id
                );
            }

            // 3. Audit
            AuditService::log(
                'sale_created',
                'sales',
                'sales',
                $saleId,
                [
                    'warehouse_id' => $data['warehouse_id'],
                    'total'        => $data['total'],
                ]
            );

            return $saleId;
        });
    }

    private static function generateSaleNumber(): string
    {
        return 'SAL-' . now()->format('YmdHis') . '-' . random_int(100, 999);
    }
}
