<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\CashRegister;
use App\Models\ExpenseCategory;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use Tests\TestCase;
use App\Services\CashRegisterService;
use App\Services\StockExpenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CashReconciliationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the full lifecycle of a cash drawer.
     * Logic: 10,000 (Open) + 5,000 (Sale) - 2,000 (Expense) = 13,000 Expected.
     */
    public function test_register_reconciliation_accounts_for_sales_and_expenses()
    {
        \Illuminate\Support\Facades\Bus::fake();

        // 1. Setup: Create business hierarchy
        $business = Business::factory()->create();
        $location = BusinessLocation::factory()->create(['business_id' => $business->id]);
        $warehouse = Warehouse::factory()->create(['business_id' => $business->id]);
        $user = User::factory()->create(['business_id' => $business->id]);

        // 🟢 TARGET 4 SETUP: Create the required Ledger Accounts
        // Your LedgerService looks for 'Cash at Hand' to credit the cash-out.
        Account::create([
            'business_id' => $business->id,
            'name' => 'Cash at Hand',
            'code' => '1001',
            'type' => 'asset',
            'is_system_account' => true,
        ]);

        // Create an account for the expense to hit
        $expenseAccount = Account::create([
            'business_id' => $business->id,
            'name' => 'General Expenses',
            'code' => '5001',
            'type' => 'expense',
            'is_system_account' => false,
        ]);

        // Create a register with 10,000 NGN opening balance
        $register = CashRegister::create([
            'business_id' => $business->id,
            'business_location_id' => $location->id,
            'user_id' => $user->id,
            'opening_amount' => 10000,
            'status' => 'open',
        ]);

        // 2. Action: Simulate a Cash Sale of 5,000 NGN
        Sale::factory()->create([
            'business_id' => $business->id,
            'business_location_id' => $location->id,
            'warehouse_id' => $warehouse->id,
            'cash_register_id' => $register->id, 
            'created_by' => $user->id,
            'subtotal' => 5000,
            'total' => 5000,
            'status' => 'completed', 
        ]);

        // 3. Action: Record an Approved Expense (Cash-Out) of 2,000 NGN
        $this->actingAs($user);

        $category = ExpenseCategory::factory()->create([
            'business_id' => $business->id,
            'ledger_account_id' => $expenseAccount->id // Link category to the ledger account
        ]);
        
        // Use app() to inject LedgerService automatically
        $expenseService = app(StockExpenseService::class);
        
        $expenseService->recordCashOut([
            'business_id' => $business->id,
            'expense_category_id' => $category->id,
            'cash_register_id' => $register->id,
            'business_location_id' => $location->id,
            'amount' => 2000,
            'note' => 'Security fee for market stall',
        ]);

        // 4. Verification: Check Expected Balance
        $registerService = app(CashRegisterService::class);
        $summary = $registerService->getRegisterSummary($register);

        // Verification of the "Expected" math
        $this->assertEquals(13000, $summary['expected_cash'], "The expected cash calculation is incorrect.");
        $this->assertEquals(5000, $summary['total_sales'], "Total sales sum failed.");
        $this->assertEquals(2000, $summary['total_expenses'], "Total expenses sum failed.");

        // 5. Final Step: Close with Actual Cash of 12,950 NGN (50 NGN shortage)
        $results = $registerService->close($register->id, 12950, 'Small shortage due to lack of change');

        $this->assertEquals(-50, $results['variance'], "Variance calculation is incorrect.");
        $this->assertEquals('shortage', $results['status'], "Status should be 'shortage'.");
        
        // Assert the register is now closed in the database
        $this->assertDatabaseHas('cash_registers', [
            'id' => $register->id,
            'status' => 'closed',
            'closing_amount' => 12950
        ]);
    }
}