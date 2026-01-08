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
     * Create and authenticate a warehouse user with fulfillment access.
     */
    protected function setupWarehouseUser(): User
    {
        Permission::findOrCreate('warehouse.fulfill');

        $business = Business::factory()->create();

        $warehouse = Warehouse::factory()->create([
            'business_id' => $business->id,
        ]);

        /**
         * We do NOT have a BusinessLocation model.
         * So we create a logical location ID and attach it to the user.
         */
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

        // Convenience for tests
        $user->warehouse_id = $warehouse->id;

        return $user;
    }

    /**
     * Create an offline fulfillment pending record (no stock guaranteed).
     */
    protected function createOfflinePending(): OfflineFulfillmentPending
    {
        $warehouse = Warehouse::factory()->create();

        $product = Product::factory()->create([
            'business_id' => $warehouse->business_id,
        ]);

        return OfflineFulfillmentPending::create([
            'sale_id'      => 1,
            'warehouse_id' => $warehouse->id,
            'payload'      => [
                'sale_id'      => 1,
                'warehouse_id' => $warehouse->id,
                'items'        => [
                    [
                        'product_id' => $product->id,
                        'quantity'   => 1,
                    ],
                ],
                'items_hash' => hash('sha256', 'test'),
                'expires_at' => now()->addHour()->timestamp,
                'nonce'      => Str::uuid()->toString(),
                'kid'        => 'test-key',
                'signature'  => 'fake-signature',
            ],
            'status' => 'pending',
        ]);
    }

    /**
     * Create an offline pending record with warehouse stock available.
     *
     * @return array [OfflineFulfillmentPending, Product, Warehouse]
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

        $pending = OfflineFulfillmentPending::create([
            'sale_id'      => 1,
            'warehouse_id' => $warehouse->id,
            'payload'      => [
                'sale_id'      => 1,
                'warehouse_id' => $warehouse->id,
                'items'        => [
                    [
                        'product_id' => $product->id,
                        'quantity'   => 1,
                    ],
                ],
                'items_hash' => hash('sha256', 'test'),
                'expires_at' => now()->addHour()->timestamp,
                'nonce'      => Str::uuid()->toString(),
                'kid'        => 'test-key',
                'signature'  => 'fake-signature',
            ],
            'status' => 'pending',
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

    // Generate deterministic sale number for tests
    $saleNumber = 'SALE-' . strtoupper(Str::random(8));

    // Create sale (ALL required fields)
    $saleId = DB::table('sales')->insertGetId([
        'business_id'          => $businessId,
        'business_location_id' => $user->business_location_id,
        'warehouse_id'         => $warehouseId,
        'sale_number'          => $saleNumber,
        'created_by'           => $userId,
        'created_at'           => now(),
        'updated_at'           => now(),
    ]);

    // Create sale item
    DB::table('sale_items')->insert([
        'sale_id'     => $saleId,
        'product_id'  => $product->id,
        'quantity'    => 1,
        'unit_price'  => 1000,
        'total_price' => 1000, // quantity × unit_price
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);



    // Seed stock
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
}
