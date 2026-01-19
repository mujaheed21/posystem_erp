<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Seeds the essential Chart of Accounts for a business.
     */
    protected function setupBusinessLedger(int $businessId): void
    {
        $accounts = [
            ['name' => 'Inventory Asset', 'code' => '1002', 'type' => 'asset'],
            ['name' => 'Accounts Receivable', 'code' => '1001', 'type' => 'asset'],
            ['name' => 'Sales Revenue', 'code' => '4001', 'type' => 'revenue'],
            ['name' => 'Cost of Goods Sold (COGS)', 'code' => '5001', 'type' => 'expense'],
        ];

        foreach ($accounts as $account) {
            DB::table('accounts')->updateOrInsert(
                ['business_id' => $businessId, 'code' => $account['code']],
                array_merge($account, [
                    'is_system_account' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }

    /**
     * Initializes stock levels and cost batches.
     * Takes $creatorId to satisfy the purchases.created_by foreign key.
     */
    protected function initializeProductStock(int $businessId, int $warehouseId, int $productId, int $creatorId, float $qty = 50, float $cost = 500): void
    {
        // 1. Create Supplier
        $supplierId = DB::table('parties')->insertGetId([
            'business_id' => $businessId,
            'name' => 'System Test Supplier',
            'type' => 'supplier',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Create Purchase (Linked to the verified User ID)
        $purchaseId = DB::table('purchases')->insertGetId([
            'business_id' => $businessId,
            'warehouse_id' => $warehouseId,
            'supplier_id' => $supplierId,
            'purchase_number' => 'PUR-AUTO-' . strtoupper(uniqid()),
            'status' => 'received',
            'created_by' => $creatorId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Physical Stock Level
        DB::table('warehouse_stock')->updateOrInsert(
            ['warehouse_id' => $warehouseId, 'product_id' => $productId],
            ['quantity' => $qty, 'reserved_quantity' => 0, 'updated_at' => now(), 'created_at' => now()]
        );

        // 4. Valuation Batch
        DB::table('stock_batches')->insert([
            'business_id' => $businessId,
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
            'purchase_id' => $purchaseId,
            'quantity_received' => $qty,
            'quantity_remaining' => $qty,
            'unit_cost' => $cost,
            'received_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}