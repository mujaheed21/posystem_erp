<?php

namespace Tests\Feature\Stock;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\WarehouseStock;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Models\WarehouseFulfillment;
use App\Models\StockMovement;
use App\Services\FulfillmentStateMachine;

class StockCommitTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function stock_is_deducted_only_on_fulfillment_release()
    {
        $business = Business::factory()->create();

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
            'quantity'          => 10,
            'reserved_quantity' => 5,
        ]);

        $sale = Sale::create([
            'business_id'          => $business->id,
            'business_location_id' => $location->id,
            'warehouse_id'         => $warehouse->id,
            'sale_number'          => 'SALE-100',
            'subtotal'             => 0,
            'discount'             => 0,
            'tax'                  => 0,
            'total'                => 0,
            'created_by'           => $user->id,
        ]);

        SaleItem::create([
            'sale_id'     => $sale->id,
            'product_id'  => $product->id,
            'quantity'    => 5,
            'unit_price'  => 100,
            'total_price' => 500,
        ]);

        $fulfillment = WarehouseFulfillment::create([
            'sale_id'      => $sale->id,
            'warehouse_id' => $warehouse->id,
            'verified_by'  => $user->id,
            'verified_at'  => now(),
        ]);

        // Act: approved → released (this triggers stock commit)
        $fulfillment = FulfillmentStateMachine::transition(
            $fulfillment,
            'approved'
        );

        $fulfillment = FulfillmentStateMachine::transition(
            $fulfillment,
            'released'
        );

        $stock = WarehouseStock::where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->first();

        $this->assertEquals(
            5,
            $stock->quantity,
            'Expected physical stock to be deducted on fulfillment release'
        );

        $this->assertEquals(
            0,
            $stock->reserved_quantity,
            'Expected reserved stock to be cleared on fulfillment release'
        );

        $this->assertDatabaseHas('stock_movements', [
            'warehouse_id'   => $warehouse->id,
            'product_id'     => $product->id,
            'type'           => 'sale',
            'reference_type' => 'warehouse_fulfillment',
            'reference_id'   => $fulfillment->id,
        ]);
    }

    /** @test */
    public function fulfillment_release_is_idempotent()
    {
        $business = Business::factory()->create();

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
            'quantity'          => 10,
            'reserved_quantity' => 5,
        ]);

        $sale = Sale::create([
            'business_id'          => $business->id,
            'business_location_id' => $location->id,
            'warehouse_id'         => $warehouse->id,
            'sale_number'          => 'SALE-101',
            'subtotal'             => 0,
            'discount'             => 0,
            'tax'                  => 0,
            'total'                => 0,
            'created_by'           => $user->id,
        ]);

        SaleItem::create([
            'sale_id'     => $sale->id,
            'product_id'  => $product->id,
            'quantity'    => 5,
            'unit_price'  => 100,
            'total_price' => 500,
        ]);

        $fulfillment = WarehouseFulfillment::create([
            'sale_id'      => $sale->id,
            'warehouse_id' => $warehouse->id,
            'verified_by'  => $user->id,
            'verified_at'  => now(),
        ]);

        // First release — commits stock
        $fulfillment = FulfillmentStateMachine::transition(
            $fulfillment,
            'approved'
        );

        $fulfillment = FulfillmentStateMachine::transition(
            $fulfillment,
            'released'
        );

        // Second attempt — must NOT commit again
        $this->expectException(\RuntimeException::class);

        FulfillmentStateMachine::transition(
            $fulfillment,
            'released'
        );

        $this->assertEquals(
            1,
            StockMovement::count(),
            'Expected only one stock movement despite repeated release'
        );

        $stock = WarehouseStock::first();

        $this->assertEquals(5, $stock->quantity);
        $this->assertEquals(0, $stock->reserved_quantity);
    }
}
