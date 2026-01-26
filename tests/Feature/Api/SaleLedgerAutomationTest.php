<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Purchase;
use App\Models\Party;
use App\Models\WarehouseStock;
use App\Models\LedgerEntry;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Helpers\SeedsLedger;

class SaleLedgerAutomationTest extends TestCase
{
    use RefreshDatabase, SeedsLedger;

    public function test_sale_completion_posts_revenue_and_cogs_to_ledger(): void
    {
        // 1. Setup Environment
        $business = Business::factory()->create(['valuation_method' => 'fifo']);
        $location = BusinessLocation::factory()->create(['business_id' => $business->id]);
        $warehouse = Warehouse::factory()->create(['business_id' => $business->id]);
        $user = User::factory()->create(['business_id' => $business->id]);
        $product = Product::factory()->create(['business_id' => $business->id]);

        $this->actingAs($user);

        // 2. Setup Chart of Accounts (COA)
        $this->seedLedgerForBusiness($business);

        // 3. Setup Stock Infrastructure
        $supplier = Party::create([
            'business_id' => $business->id,
            'name' => 'Test Supplier',
            'type' => 'supplier',
            'phone' => '08012345678'
        ]);

        $purchase = Purchase::create([
            'business_id' => $business->id,
            'warehouse_id' => $warehouse->id,
            'supplier_id' => $supplier->id,
            'purchase_number' => 'PUR-INIT-001',
            'status' => 'received',
            'created_by' => $user->id,
            'subtotal' => 10000.00,
            'total' => 10000.00,
            'total_amount' => 10000.00,
        ]);

        WarehouseStock::create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 10.000,
            'reserved_quantity' => 0.000,
        ]);

        DB::table('stock_batches')->insert([
            'business_id' => $business->id,
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'purchase_id' => $purchase->id,
            'quantity_received' => 10,
            'quantity_remaining' => 10,
            'unit_cost' => 1000.00, 
            'received_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 4. Action: Execute Sale
        $service = app(SaleService::class);

        $saleData = [
            'business_id' => $business->id,
            'business_location_id' => $location->id,
            'warehouse_id' => $warehouse->id,
            'sale_number' => 'SALE-001',
            'subtotal' => 5000,
            'total' => 5000,
            'status' => 'final',
            'created_by' => $user->id,
        ];

        $items = [
            [
                'product_id' => $product->id,
                'quantity' => 2,
                'unit_price' => 2500,
            ]
        ];

        // The service returns the ID as an integer
        $saleId = $service->create($saleData, $items);

        // 5. Assertions: Revenue Logic (Target 4)
        $this->assertDatabaseHas('ledger_entries', [
            'business_id' => $business->id,
            'debit' => 5000,
            'source_type' => 'App\\Models\\Sale',
            'source_id' => $saleId // Use the returned integer directly
        ]);

        // 6. Assertions: Valuation Logic (Target 5)
        $cogsAccount = Account::where('business_id', $business->id)
            ->where('name', 'Cost of Goods Sold (COGS)') 
            ->first();

        $this->assertDatabaseHas('ledger_entries', [
            'account_id' => $cogsAccount->id,
            'debit' => 2000,
            'source_type' => 'App\\Models\\Sale',
            'source_id' => $saleId
        ]);

        // 7. Verify Ledger Balance
        $totals = LedgerEntry::where('business_id', $business->id)
            ->where('source_id', $saleId)
            ->selectRaw('SUM(debit) as d, SUM(credit) as c')
            ->first();

        $this->assertEquals($totals->d, $totals->c, "Ledger is imbalanced: Debits do not equal Credits.");
        $this->assertEquals(7000, (float)$totals->d);
    }
}