<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\StockService;
use App\Services\AuditService;
use App\Services\LedgerService; // Added
use App\Models\Purchase; // Added
use App\Models\Product; // Added

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

                // NEW: Update Product Cost Price
                // This is vital so the SaleService knows the value of the stock for COGS
                Product::where('id', $item['product_id'])
                    ->update(['cost_price' => $item['unit_cost']]);
// RIGHT: Fetch the model and pass it
            // Fetch the warehouse model
                $warehouse = \App\Models\Warehouse::find($data['warehouse_id']);

                app(StockService::class)->increase(
                    $warehouse,             // 1. Warehouse Object
                    $item['product_id'],    // 2. Product ID
                    $item['quantity'],       // 3. Qty
                    'purchase',             // 4. Source Type (string)
                    $purchaseId,            // 5. Source ID (int) - MUST BE HERE
                    'purchases',            // 6. Source Table (string)
                    $user->id               // 7. User ID
                );
            }

            // 3. NEW: Financial Posting to General Ledger
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
            // CREDIT Accounts Payable (Liability increases - you owe the supplier)
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