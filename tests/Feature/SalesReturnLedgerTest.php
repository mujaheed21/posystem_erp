<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Business;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SalesReturnLedgerTest extends TestCase
{
    use RefreshDatabase;

    protected function seedLedgerAccounts(int $businessId)
    {
        $accounts = [
            ['name' => 'Inventory Asset', 'code' => '1002', 'type' => 'asset'],
            ['name' => 'Accounts Receivable', 'code' => '1001', 'type' => 'asset'],
            ['name' => 'Sales Revenue', 'code' => '4001', 'type' => 'revenue'],
        ];

        foreach ($accounts as $account) {
            Account::create(array_merge($account, [
                'business_id' => $businessId,
                'is_system_account' => 1
            ]));
        }
    }

    #[Test]
    public function a_sale_return_reverses_financial_and_stock_impact()
    {
        // 1. Setup Environment
        $business = Business::factory()->create();
        $this->seedLedgerAccounts($business->id);
        
        $locationId = DB::table('business_locations')->insertGetId([
            'business_id' => $business->id,
            'name' => 'Main Shop',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::factory()->create(['business_id' => $business->id]);
        $warehouse = Warehouse::factory()->create(['business_id' => $business->id]);
        $product = Product::factory()->create(['business_id' => $business->id, 'cost_price' => 500]);
        
        // NEW: Create the Stock Record so the StockService can find it
        DB::table('warehouse_stock')->insert([
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'quantity'     => 49, // Give it some starting stock
            'reserved_quantity' => 0,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $this->actingAs($user);
        $saleService = app(SaleService::class);

        // 2. Create an initial Sale (₦1,000)
        $saleId = $saleService->create([
            'business_id' => $business->id,
            'business_location_id' => $locationId,
            'warehouse_id' => $warehouse->id,
            'sale_number' => 'SALE-REV-001',
            'subtotal' => 1000,
            'total' => 1000,
        ], [
            ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 1000]
        ]);

        // 3. Execute the Return
        $saleService->processReturn($saleId, [
            ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 1000]
        ]);

        // --- ASSERTIONS ---

        // 4. Verify stock returned (50 - 1 sold + 1 returned = 50)
        $this->assertDatabaseHas('warehouse_stock', [
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'quantity'     => 50
        ]);

        // 5. Verify AR Balance is 0
        $arAccount = Account::where('name', 'Accounts Receivable')->first();
        $arBalance = DB::table('ledger_entries')
            ->where('account_id', $arAccount->id)
            ->selectRaw('SUM(debit) - SUM(credit) as balance')
            ->first()->balance;
        $this->assertEquals(0, $arBalance);

        // 6. Verify Revenue Balance is 0
        $revAccount = Account::where('name', 'Sales Revenue')->first();
        $revBalance = DB::table('ledger_entries')
            ->where('account_id', $revAccount->id)
            ->selectRaw('SUM(credit) - SUM(debit) as balance')
            ->first()->balance;
        $this->assertEquals(0, $revBalance);
    }
}