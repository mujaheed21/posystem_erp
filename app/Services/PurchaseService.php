<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\StockService;
use App\Services\AuditService;
use App\Services\LedgerService;
use App\Models\Purchase;
use App\Models\Product;
use App\Models\Warehouse;

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

            $warehouse = Warehouse::find($data['warehouse_id']);

            // 2. Create purchase items, stock batches & increase stock
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

                // NEW: Create Stock Batch for FIFO/Average Valuation
                // This tracks exactly how much of this specific 'lot' is left.
                DB::table('stock_batches')->insert([
                    'business_id'        => $user->business_id,
                    'warehouse_id'       => $data['warehouse_id'],
                    'product_id'         => $item['product_id'],
                    'purchase_id'        => $purchaseId,
                    'quantity_received'  => $item['quantity'],
                    'quantity_remaining' => $item['quantity'],
                    'unit_cost'          => $item['unit_cost'],
                    'received_at'        => now(),
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);

                // Update Product Cost Price (Used as a fallback/quick reference)
                Product::where('id', $item['product_id'])
                    ->update(['cost_price' => $item['unit_cost']]);

                // Increase Physical Stock
                app(StockService::class)->increase(
                    $warehouse,             
                    $item['product_id'],    
                    $item['quantity'],      
                    'purchase',             
                    $purchaseId,            
                    'purchases',            
                    $user->id               
                );
            }

            // 3. Financial Posting to General Ledger
            $purchase = Purchase::find($purchaseId);
            self::recordFinancialEntry($purchase);

            // 4. Audit
            AuditService::log(
                'purchase_received',
                'procurement',
                'purchases',
                $purchaseId,
                [
                    'warehouse_id' => $data['warehouse_id'],
                    'total'        => $data['total'],
                    'batches_created' => count($data['items'])
                ]
            );

            return $purchaseId;
        });
    }

    /**
     * Post the purchase to the Ledger.
     */
    protected static function recordFinancialEntry($purchase)
    {
        $ledgerService = app(LedgerService::class);
        $businessId = $purchase->business_id;

        $entries = [
            // DEBIT Inventory Asset (Asset increases)
            [
                'account_code' => $ledgerService->getCode($businessId, 'Inventory Asset'),
                'debit' => $purchase->total,
                'credit' => 0
            ],
            // CREDIT Accounts Payable (Liability increases)
            [
                'account_code' => $ledgerService->getCode($businessId, 'Accounts Payable'),
                'debit' => 0,
                'credit' => $purchase->total
            ]
        ];

        $ledgerService->post(
            $businessId,
            $entries,
            $purchase,
            "Stock purchase received. Ref: #{$purchase->purchase_number}"
        );
    }

    private static function generatePurchaseNumber(): string
    {
        return 'PUR-' . now()->format('YmdHis') . '-' . random_int(100, 999);
    }
}