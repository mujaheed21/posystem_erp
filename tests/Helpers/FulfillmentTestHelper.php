<?php

namespace Tests\Helpers;

use App\Models\User;
use App\Models\Business;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\WarehouseStock;
use App\Models\OfflineFulfillmentPending;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

trait FulfillmentTestHelper
{
    /**
     * Create and authenticate a warehouse user with fulfill permission.
     */
    protected function setupWarehouseUser(): User
    {
        Permission::findOrCreate('warehouse.fulfill');

        $business = Business::factory()->create();

        $warehouse = Warehouse::factory()->create([
            'business_id' => $business->id,
        ]);

        $businessLocationId = DB::table('business_locations')->insertGetId([
            'business_id' => $business->id,
            'name'        => 'Test Location',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $user = User::factory()->create([
            'business_id'          => $business->id,
            'business_location_id' => $businessLocationId,
        ]);

        $user->givePermissionTo('warehouse.fulfill');
        Sanctum::actingAs($user);

        // convenience for tests
        $user->warehouse_id = $warehouse->id;

        return $user;
    }

    /**
     * Create a basic offline pending fulfillment (NO stock guaranteed).
     */
    protected function createOfflinePending(): OfflineFulfillmentPending
    {
        $warehouse = Warehouse::factory()->create();

        $product = Product::factory()->create([
            'business_id' => $warehouse->business_id,
        ]);

       $user = $this->createTestUserWithLocation($warehouse->business_id);

$saleId = DB::table('sales')->insertGetId([
    'business_id'          => $warehouse->business_id,
    'business_location_id' => $user->business_location_id,
    'warehouse_id'         => $warehouse->id,
    'sale_number'          => 'OFFLINE-' . Str::upper(Str::random(8)),
    'created_by'           => $user->id,
    'created_at'           => now(),
    'updated_at'           => now(),
]);

return OfflineFulfillmentPending::create([
    'sale_id'      => $saleId,
    'warehouse_id' => $warehouse->id,
    'state'       => 'pending',
    'payload'      => [
        'items' => [
            [
                'product_id' => $product->id,
                'quantity'   => 1,
            ],
        ],
    ],
]);

    }

    /**
     * Create an offline pending fulfillment WITH stock.
     *
     * RETURN ORDER IS STRICT:
     * [OfflineFulfillmentPending, Product, Warehouse]
     */
    protected function createOfflinePendingWithStock(): array
    {
        $warehouse = Warehouse::factory()->create();

        $product = Product::factory()->create([
            'business_id' => $warehouse->business_id,
        ]);

        WarehouseStock::create([
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'quantity'     => 10,
        ]);

        $user = $this->createTestUserWithLocation($warehouse->business_id);

$saleId = DB::table('sales')->insertGetId([
    'business_id'          => $warehouse->business_id,
    'business_location_id' => $user->business_location_id,
    'warehouse_id'         => $warehouse->id,
    'sale_number'          => 'OFFLINE-' . Str::upper(Str::random(8)),
    'created_by'           => $user->id,
    'created_at'           => now(),
    'updated_at'           => now(),
]);

$pending = OfflineFulfillmentPending::create([
    'sale_id'      => $saleId,
    'warehouse_id' => $warehouse->id,
    'state'       => 'pending',
    'payload'      => [
        'items' => [
            [
                'product_id' => $product->id,
                'quantity'   => 1,
            ],
        ],
    ],
]);


        return [$pending, $product, $warehouse];
    }

    /**
     * Create a sale with a single item and seeded warehouse stock.
     */
    protected function createSaleWithItem(
        int $businessId,
        int $userId,
        int $warehouseId
    ): int {
        $user = User::findOrFail($userId);

        $product = Product::factory()->create([
            'business_id' => $businessId,
        ]);

        $saleNumber = 'SALE-' . strtoupper(Str::random(8));

        $saleId = DB::table('sales')->insertGetId([
            'business_id'          => $businessId,
            'business_location_id' => $user->business_location_id,
            'warehouse_id'         => $warehouseId,
            'sale_number'          => $saleNumber,
            'created_by'           => $userId,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        DB::table('sale_items')->insert([
            'sale_id'     => $saleId,
            'product_id'  => $product->id,
            'quantity'    => 1,
            'unit_price'  => 1000,
            'total_price' => 1000,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        WarehouseStock::updateOrCreate(
            [
                'warehouse_id' => $warehouseId,
                'product_id'   => $product->id,
            ],
            [
                'quantity' => 10,
            ]
        );

        return $saleId;
    }
    protected function createTestUserWithLocation(int $businessId): User
{
    $locationId = DB::table('business_locations')->insertGetId([
        'business_id' => $businessId,
        'name'        => 'Test Location',
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);

    return User::factory()->create([
        'business_id'          => $businessId,
        'business_location_id' => $locationId,
    ]);
}

}
