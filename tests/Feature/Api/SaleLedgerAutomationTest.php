<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleLedgerAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_completion_posts_revenue_and_cogs_to_ledger(): void
    {
        // 1. Setup Environment
        $business = Business::factory()->create();
        $location = BusinessLocation::factory()->create(['business_id' => $business->id]);
        $warehouse = Warehouse::factory()->create(['business_id' => $business->id]);
        $user = User::factory()->create(['business_id' => $business->id]);
        $product = Product::factory()->create(['business_id' => $business->id]);

        // 2. Setup Chart of Accounts (COA)
        $accounts = [
            ['name' => 'Cash at Hand', 'code' => '1001', 'type' => 'asset'],
            ['name' => 'Inventory Asset', 'code' => '1005', 'type' => 'asset'],
            ['name' => 'Sales Revenue', 'code' => '4001', 'type' => 'revenue'],
            ['name' => 'Cost of Goods Sold (COGS)', 'code' => '5001', 'type' => 'expense'],
        ];

        foreach ($accounts as $acc) {
            Account::create(array_merge($acc, ['business_id' => $business->id]));
        }

        // 3. Setup Stock (Assume we have 10 units that cost 1,000 NGN each)
        // This simulates your ValuationService having batches to consume
        \DB::table('stock_batches')->insert([
            'business_id' => $business->id,
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity_received' => 10,
            'quantity_remaining' => 10,
            'purchase_price' => 1000, // The "Cost" for COGS
            'created_at' => now(),
        ]);

        // 4. Action: Sell 2 units at 2,500 NGN each
        $this->actingAs($user);
        $service = app(SaleService::class);

        $saleData = [
            'business_id' => $business->id,
            'business_location_id' => $location->id,
            'warehouse_id' => $warehouse->id,
            'cash_register_id' => null,
            'sale_number' => 'SALE-001',
            'subtotal' => 5000,
            'total' => 5000,
        ];

        $items = [
            [
                'product_id' => $product->id,
                'quantity' => 2,
                'unit_price' => 2500,
            ]
        ];

        $service->create($saleData, $items);

        // 5. Assertions: Revenue Logic (Target 4)
        // Total Sale = 5,000. Cash (Debit 5000) / Revenue (Credit 5000)
        $this->assertDatabaseHas('ledger_entries', [
            'debit' => 5000,
            'description' => 'Automated financial posting for Sale #SALE-001'
        ]);

        // 6. Assertions: Valuation Logic (Target 5)
        // 2 units * 1,000 cost = 2,000 COGS.
        // COGS (Debit 2000) / Inventory (Credit 2000)
        $this->assertDatabaseHas('ledger_entries', [
            'account_id' => Account::where('name', 'Cost of Goods Sold (COGS)')->first()->id,
            'debit' => 2000,
        ]);

        $this->assertDatabaseHas('ledger_entries', [
            'account_id' => Account::where('name', 'Inventory Asset')->first()->id,
            'credit' => 2000,
        ]);

        // 7. Verify fundamental equation: Total Debits == Total Credits for this source
        $totals = \App\Models\LedgerEntry::selectRaw('SUM(debit) as d, SUM(credit) as c')->first();
        $this->assertEquals($totals->d, $totals->c);
        $this->assertEquals(7000, $totals->d); // 5000 (Revenue move) + 2000 (Valuation move)
    }
}