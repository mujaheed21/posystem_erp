<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\ExpenseCategory;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ExpenseService;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Helpers\SeedsLedger;

class NetProfitTest extends TestCase
{
    use RefreshDatabase, SeedsLedger;

    /**
     * This test verifies the complete financial journey:
     * 1. Stock is purchased (Inventory Asset).
     * 2. Product is sold (Revenue + COGS + Gross Profit).
     * 3. Operational expense is paid (Cash Out + Expense Ledger).
     * 4. P&L reflects the final Net Profit.
     */
    public function test_it_calculates_correct_net_profit_after_operating_expenses()
    {
        // --- 1. SETUP ENVIRONMENT ---
        $business = Business::factory()->create(['valuation_method' => 'fifo']);
        $user = User::factory()->create(['business_id' => $business->id]);
        $location = BusinessLocation::factory()->create(['business_id' => $business->id]);
        $warehouse = Warehouse::factory()->create(['business_id' => $business->id]);
        
        // Product: Cost 700, Retail 1000
        $product = Product::factory()->create([
            'business_id' => $business->id,
            'selling_price' => 1000, 
            'reorder_level' => 5
        ]);

        $this->actingAs($user);
        
        // Infrastructure Setup via Helpers
        $this->seedLedgerForBusiness($business);
        
        // Create an Expense Account and link it to a Category using the correct schema
        $expenseAccount = Account::create([
            'business_id' => $business->id,
            'name' => 'General Operating Expense',
            'type' => 'expense',
            'code' => '6001',
            'is_system' => false
        ]);

        $category = ExpenseCategory::factory()->create([
            'business_id' => $business->id,
            'name' => 'General Operations',
            'ledger_account_id' => $expenseAccount->id // Corrected from account_id
        ]);
        
        // Open Register with 5,000 NGN opening cash
        $this->openRegister($user->id, $location->id, 5000.00);
        
        // Initialize Stock: 10 units @ 700.00 NGN cost
        $this->initializeProductStock($business->id, $warehouse->id, $product->id, $user->id, 10, 700.00);

        // --- 2. EXECUTE SALE (GROSS PROFIT IMPACT) ---
        // Expected: 1000 Revenue - 700 COGS = 300 Gross Profit
        $saleService = app(SaleService::class);
        $saleService->create([
            'business_id' => $business->id,
            'business_location_id' => $location->id,
            'warehouse_id' => $warehouse->id,
            'sale_number' => 'SALE-NET-001',
            'subtotal' => 1000.00,
            'total' => 1000.00,
            'status' => 'final',
            'created_by' => $user->id,
        ], [
            ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 1000.00]
        ]);

        // --- 3. EXECUTE EXPENSE (NET PROFIT IMPACT) ---
        // Record 100 NGN expense
        $expenseService = app(ExpenseService::class);
        $expenseService->record([
            'business_id' => $business->id,
            'expense_category_id' => $category->id,
            'business_location_id' => $location->id,
            'ref_no' => 'EXP-TEST-01',
            'amount' => 100.00,
            'operation_date' => now()->toDateString(),
            'note' => 'Test operating expense',
            'created_by' => $user->id,
        ]);

        // --- 4. VERIFY ANALYTICS ENGINE ---
        $today = now()->toDateString();
        $response = $this->getJson("/api/v1/reports/profit-loss?start_date={$today}&end_date={$today}");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJson([
                'data' => [
                    'revenue'        => 1000,
                    'cogs'           => 700,
                    'gross_profit'   => 300,
                    'total_expenses' => 100,
                    'net_profit'     => 200,
                ]
            ]);
            
        // --- 5. VERIFY REGISTER RECONCILIATION ---
        $this->assertDatabaseHas('cash_registers', [
            'user_id' => $user->id,
            'total_cash_expenses' => 100.00,
        ]);
        
        // --- 6. VERIFY LEDGER INTEGRITY ---
        // Find the 'Cash at Hand' account
        $cashAccount = Account::where('business_id', $business->id)
            ->where('name', 'Cash at Hand')
            ->first();

        // Ensure the Cash Account was credited (decreased) for the expense
        $cashCredit = DB::table('ledger_entries')
            ->where('account_id', $cashAccount->id)
            ->where('source_type', 'expense')
            ->sum('credit');
            
        $this->assertEquals(100.00, (float)$cashCredit);

        // Ensure General Expenses account was debited (increased)
        $expenseDebit = DB::table('ledger_entries')
            ->where('account_id', $expenseAccount->id)
            ->where('source_type', 'expense')
            ->sum('debit');

        $this->assertEquals(100.00, (float)$expenseDebit);
    }
}