<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\StockService;
use App\Services\AuditService;

class SaleService
{
    /**
     * Create a sale and reserve stock atomically.
     *
     * @param array $saleData   Sale header data
     * @param array $items      Sale items
     */
    public function create(array $saleData, array $items)
    {
        return DB::transaction(function () use ($saleData, $items) {

            $user = Auth::user();

            // 1. Create sale header
            $saleId = DB::table('sales')->insertGetId([
                'business_id'          => $saleData['business_id'],
                'business_location_id' => $saleData['business_location_id'],
                'warehouse_id'         => $saleData['warehouse_id'],
                'sale_number'          => $saleData['sale_number'],
                'subtotal'             => $saleData['subtotal'],
                'discount'             => $saleData['discount'] ?? 0,
                'tax'                  => $saleData['tax'] ?? 0,
                'total'                => $saleData['total'],
                'status'               => 'completed', // legacy, ignored by stock logic
                'created_by'           => $saleData['created_by'],
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);

            // 2. Create sale items
            $reservationItems = [];

            foreach ($items as $item) {

                DB::table('sale_items')->insert([
                    'sale_id'     => $saleId,
                    'product_id'  => $item['product_id'],
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);

                $reservationItems[] = [
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                ];
            }

            // 3. Reserve stock (INTENT ONLY — no ledger, no audit)
            app(StockService::class)->reserve(
                $saleData['warehouse_id'],
                $reservationItems
            );

            // 4. Audit sale creation (business event, not stock event)
            AuditService::log(
                'sale_created',
                'sales',
                'sales',
                $saleId,
                [
                    'warehouse_id' => $saleData['warehouse_id'],
                    'total'        => $saleData['total'],
                ]
            );

            return $saleId;
        });
    }
}
