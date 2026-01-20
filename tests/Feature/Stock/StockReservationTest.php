<?php

namespace Tests\Feature\Stock;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\WarehouseStock;
use App\Models\User;
use App\Models\Account; // Added

class StockReservationTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Helper to seed accounts required by the LedgerService during Sale creation.
     */
    protected function seedLedgerAccounts(int $businessId)
    {
        $accounts = [
            ['name' => 'Accounts Receivable', 'code' => '1001', 'type' => 'asset'],
            ['name' => 'Sales Revenue', 'code' => '4001', 'type' => 'revenue'],
            ['name' => 'Inventory Asset', 'code' => '1002', 'type' => 'asset'],
            ['name' => 'Cost of Goods Sold (COGS)', 'code' => '5001', 'type' => 'expense'],
        ];

        foreach ($accounts as $account) {
            Account::create(array_merge($account, ['business_id' => $businessId]));
        }
    }

    /** @test */
    public function stock_is_reserved_at_sale_creation()
    {
        $business = Business::factory()->create();
        
        // NEW: Seed ledger accounts so SaleService doesn't fail
        $this->seedLedgerAccounts($business->id);

        $location = BusinessLocation::factory()->create([
            'business_id' => $business->id,
        ]);

        $user = User::factory()->create([
            'business_id'          => $business->id,
            'business_location_id' => $location->id,
        ]);

        $warehouse = Warehouse::factory()->create([
            'business_id' => $business->id,
        ]);

        $product = Product::factory()->create([
            'business_id' => $business->id,
            'cost_price'  => 50, // Added to ensure COGS calculation works
        ]);

        WarehouseStock::create([
            'warehouse_id'      => $warehouse->id,
            'product_id'        => $product->id,
            'quantity'          => 10,
            'reserved_quantity' => 0,
        ]);

        $this->actingAs($user);

        $saleService = app(\App\Services\SaleService::class);

        $saleService->create(
            [
                'business_id'          => $business->id,
                'business_location_id' => $location->id,
                'warehouse_id'         => $warehouse->id,
                'sale_number'          => 'SALE-001',
                'subtotal'             => 300,
                'discount'             => 0,
                'tax'                  => 0,
                'total'                => 300,
                'created_by'           => $user->id,
            ],
            [
                [
                    'product_id' => $product->id,
                    'quantity'   => 3,
                    'unit_price' => 100,
                ],
            ]
        );

        $stock = WarehouseStock::where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->first();

        $this->assertEquals(
            3,
            $stock->reserved_quantity,
            'Expected reserved_quantity to increase at sale creation'
        );

        $this->assertEquals(
            10,
            $stock->quantity,
            'Physical stock quantity must not change during reservation'
        );
    }

    /** @test */
    public function sale_creation_fails_if_stock_is_insufficient()
    {
        $business = Business::factory()->create();
        
        // NEW: Seed ledger accounts
        $this->seedLedgerAccounts($business->id);

        $location = BusinessLocation::factory()->create([
            'business_id' => $business->id,
        ]);

        $user = User::factory()->create([
            'business_id'          => $business->id,
            'business_location_id' => $location->id,
        ]);

        $warehouse = Warehouse::factory()->create([
            'business_id' => $business->id,
        ]);

        $product = Product::factory()->create([
            'business_id' => $business->id,
        ]);

        WarehouseStock::create([
            'warehouse_id'      => $warehouse->id,
            'product_id'        => $product->id,
            'quantity'          => 2,
            'reserved_quantity' => 1,
        ]);

        $this->actingAs($user);

        $this->expectException(\RuntimeException::class);

        $saleService = app(\App\Services\SaleService::class);

        $saleService->create(
            [
                'business_id'          => $business->id,
                'business_location_id' => $location->id,
                'warehouse_id'         => $warehouse->id,
                'sale_number'          => 'SALE-002',
                'subtotal'             => 300,
                'discount'             => 0,
                'tax'                  => 0,
                'total'                => 300,
                'created_by'           => $user->id,
            ],
            [
                [
                    'product_id' => $product->id,
                    'quantity'   => 3,
                    'unit_price' => 100,
                ],
            ]
        );
    }
}