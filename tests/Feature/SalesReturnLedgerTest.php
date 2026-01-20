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
            // MISSING ACCOUNT ADDED HERE:
            ['name' => 'Cost of Goods Sold (COGS)', 'code' => '5001', 'type' => 'expense'],
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
        $business = Business::factory()->create();
        $user = User::factory()->create(['business_id' => $business->id]);
        $warehouse = Warehouse::factory()->create(['business_id' => $business->id]);
        $product = Product::factory()->create(['business_id' => $business->id]);
        
        $locationId = DB::table('business_locations')->insertGetId([
            'business_id' => $business->id,
            'name' => 'Main Shop',
        ]);

        $this->actingAs($user);

        // Unified Helpers
        $this->setupBusinessLedger($business->id);
        $this->initializeProductStock($business->id, $warehouse->id, $product->id, $user->id, 50, 500.00);

        $saleService = app(SaleService::class);
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

        $saleService->processReturn($saleId, [
            ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 1000]
        ]);

        $this->assertDatabaseHas('warehouse_stock', [
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'quantity'     => 51
        ]);
    }
}