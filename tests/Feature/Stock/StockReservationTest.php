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
use App\Models\Account;
use Tests\Helpers\SeedsLedger;

class StockReservationTest extends TestCase
{
    use RefreshDatabase, SeedsLedger;

    /** @test */
    public function stock_is_reserved_at_sale_creation()
    {
        // --- 1. SETUP ENVIRONMENT ---
        $business = Business::factory()->create();
        
        // Use the centralized helper to seed 'Cash at Hand', 'COGS', etc.
        $this->seedLedgerForBusiness($business);

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
            'selling_price' => 100,
        ]);

        // Initial stock: 10 physical units, 0 reserved
        WarehouseStock::create([
            'warehouse_id'      => $warehouse->id,
            'product_id'        => $product->id,
            'quantity'          => 10,
            'reserved_quantity' => 0,
        ]);

        $this->actingAs($user);

        $saleService = app(\App\Services\SaleService::class);

        // --- 2. EXECUTE RESERVATION ---
        $saleService->create(
            [
                'business_id'          => $business->id,
                'business_location_id' => $location->id,
                'warehouse_id'         => $warehouse->id,
                'sale_number'          => 'SALE-001',
                'subtotal'             => 300,
                'total'                => 300,
                'status'               => 'draft', // Draft status usually triggers reservation
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

        // --- 3. VERIFY STOCK STATE ---
        $stock = WarehouseStock::where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->first();

        $this->assertEquals(
            3,
            (float) $stock->reserved_quantity,
            'Expected reserved_quantity to increase at sale creation'
        );

        $this->assertEquals(
            10,
            (float) $stock->quantity,
            'Physical stock quantity must not change during reservation'
        );
    }

    /** @test */
    public function sale_creation_fails_if_stock_is_insufficient()
    {
        // --- 1. SETUP ENVIRONMENT ---
        $business = Business::factory()->create();
        $this->seedLedgerForBusiness($business);

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

        // 2 units physical, 1 reserved. Available = 1.
        WarehouseStock::create([
            'warehouse_id'      => $warehouse->id,
            'product_id'        => $product->id,
            'quantity'          => 2,
            'reserved_quantity' => 1,
        ]);

        $this->actingAs($user);

        // --- 2. EXECUTE & ASSERT FAILURE ---
        // Expecting failure because we are trying to sell 3 when only 1 is available
        $this->expectException(\RuntimeException::class);

        $saleService = app(\App\Services\SaleService::class);

        $saleService->create(
            [
                'business_id'          => $business->id,
                'business_location_id' => $location->id,
                'warehouse_id'         => $warehouse->id,
                'sale_number'          => 'SALE-002',
                'subtotal'             => 300,
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