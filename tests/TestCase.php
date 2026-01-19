<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use App\Models\Account;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Seeds the essential Chart of Accounts for a business.
     * Uses the Account Model to ensure LedgerService lookups (firstOrFail) succeed.
     */
  protected function setupBusinessLedger(int $businessId): void
{
    $accounts = [
        ['name' => 'Sales Revenue', 'code' => '4001', 'type' => 'revenue'],
        
        // Updated to match SaleService requirement
        ['name' => 'Inventory Asset', 'code' => '1002', 'type' => 'asset'],
        
        ['name' => 'Cost of Goods Sold (COGS)', 'code' => '5001', 'type' => 'expense'],
        ['name' => 'Cash', 'code' => '1001', 'type' => 'asset'], 
        ['name' => 'General Expenses', 'code' => '6001', 'type' => 'expense'],
        ['name' => 'Accounts Receivable', 'code' => '1003', 'type' => 'asset'],
        ['name' => 'VAT Payable', 'code' => '2001', 'type' => 'liability'],
    ];

    foreach ($accounts as $accountData) {
        \App\Models\Account::updateOrCreate(
            ['business_id' => $businessId, 'code' => $accountData['code']],
            array_merge($accountData, [
                'is_system_account' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ])
        );
    }
}
    /**
     * Setup standard expense categories for testing.
     * Must be called AFTER setupBusinessLedger.
     */
    protected function setupExpenseCategories(int $businessId): void
    {
        $generalExpenseAccount = DB::table('accounts')
            ->where('business_id', $businessId)
            ->where('code', '6001')
            ->first();

        if (!$generalExpenseAccount) {
            throw new \Exception("Ledger Account 6001 not found for Business $businessId. Ensure setupBusinessLedger runs first.");
        }

        DB::table('expense_categories')->insertOrIgnore([
            [
                'business_id' => $businessId,
                'name' => 'General Operations',
                'code' => 'GEN',
                'ledger_account_id' => $generalExpenseAccount->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }

    /**
     * Initializes stock levels and cost batches.
     * Provides the foundation for COGS calculation.
     */
    protected function initializeProductStock(int $businessId, int $warehouseId, int $productId, int $creatorId, float $qty = 50, float $cost = 500): void
    {
        $supplierId = DB::table('parties')->insertGetId([
            'business_id' => $businessId,
            'name' => 'System Test Supplier',
            'type' => 'supplier',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

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

        DB::table('warehouse_stock')->updateOrInsert(
            ['warehouse_id' => $warehouseId, 'product_id' => $productId],
            ['quantity' => $qty, 'reserved_quantity' => 0, 'updated_at' => now(), 'created_at' => now()]
        );

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

    /**
     * Simulates opening a cash register for a user.
     * Required for Expense and Sale transactions involving cash.
     */
    protected function openRegister(int $userId, int $locationId, float $openingAmount = 0): int
    {
        $user = DB::table('users')->where('id', $userId)->first();

        return DB::table('cash_registers')->insertGetId([
            'business_id' => $user->business_id,
            'user_id' => $userId,
            'business_location_id' => $locationId,
            'status' => 'open',
            'opening_amount' => $openingAmount,
            'total_cash_sales' => 0,
            'total_cash_expenses' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}