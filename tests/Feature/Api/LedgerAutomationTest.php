<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\CashRegister;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Services\StockExpenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LedgerAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_approval_posts_balanced_ledger_entries(): void
    {
        // 1. Setup Hierarchy
        $business = Business::factory()->create();
        $location = BusinessLocation::factory()->create(['business_id' => $business->id]);
        $user = User::factory()->create(['business_id' => $business->id]);
        
        // 2. Setup Cash Register (Target 6 Reference)
        $register = CashRegister::create([
            'business_id' => $business->id,
            'business_location_id' => $location->id,
            'user_id' => $user->id,
            'status' => 'open',
            'opening_amount' => 10000.00,
        ]);

        // 3. Setup Accounting (Target 4 Infrastructure)
        $cashAccount = Account::create([
            'business_id' => $business->id,
            'name' => 'Cash at Hand', 
            'code' => '1001',
            'type' => 'asset',
        ]);

        $expenseAccount = Account::create([
            'business_id' => $business->id,
            'name' => 'General Expense',
            'code' => '5001',
            'type' => 'expense',
        ]);

        $category = ExpenseCategory::create([
            'business_id' => $business->id,
            'name' => 'Utilities',
            'ledger_account_id' => $expenseAccount->id
        ]);

        // 4. Action: Record 3,000 NGN Expense (Auto-approved because <= 5000)
        $this->actingAs($user);
        $service = app(StockExpenseService::class);
        
        $amount = 3000.00;
        $expense = $service->recordCashOut([
            'business_id' => $business->id,
            'business_location_id' => $location->id,
            'cash_register_id' => $register->id,
            'expense_category_id' => $category->id,
            'amount' => $amount,
            'operation_date' => now()->toDateString(),
        ]);

        // 5. Assert: Operational Record Created
        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'status' => 'approved',
            'cash_register_id' => $register->id
        ]);

        // 6. Assert: Ledger Entries Created (Target 4 Automation)
        $this->assertDatabaseHas('ledger_entries', [
            'account_id' => $expenseAccount->id,
            'debit' => $amount,
            'source_id' => $expense->id,
            'source_type' => Expense::class
        ]);

        $this->assertDatabaseHas('ledger_entries', [
            'account_id' => $cashAccount->id,
            'credit' => $amount,
            'source_id' => $expense->id,
            'source_type' => Expense::class
        ]);
    }
}