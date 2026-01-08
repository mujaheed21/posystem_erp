<?php

namespace Tests\Helpers;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

trait FulfillmentTestHelper
{
    /**
     * Create and authenticate a warehouse user with fulfillment access.
     */
    protected function setupWarehouseUser(): User
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        // Ensure permission exists
        $permission = Permission::firstOrCreate([
            'name' => 'warehouse.fulfill',
            'guard_name' => 'web',
        ]);

        $user->givePermissionTo($permission);

        // Create business
        $businessId = DB::table('businesses')->insertGetId([
            'name'       => 'Test Business',
            'slug'       => uniqid('test-biz-'),
            'active'     => true,
            'created_at'=> now(),
            'updated_at'=> now(),
        ]);

        // Create business location
        $locationId = DB::table('business_locations')->insertGetId([
            'business_id'=> $businessId,
            'name'       => 'Main Location',
            'code'       => uniqid('LOC-'),
            'active'     => true,
            'created_at'=> now(),
            'updated_at'=> now(),
        ]);

        // Create warehouse
        $warehouseId = DB::table('warehouses')->insertGetId([
            'business_id'=> $businessId,
            'name'       => 'Main Warehouse',
            'code'       => uniqid('WH-'),
            'active'     => true,
            'created_at'=> now(),
            'updated_at'=> now(),
        ]);

        // Link location ↔ warehouse
        DB::table('business_location_warehouse')->insert([
            'business_location_id' => $locationId,
            'warehouse_id'         => $warehouseId,
            'access_level'         => 'fulfill',
            'active'               => true,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        // Attach business context to user
        $user->update([
            'business_id'          => $businessId,
            'business_location_id' => $locationId,
        ]);

        // Expose IDs for tests
        $user->business_id = $businessId;
        $user->warehouse_id = $warehouseId;
        $user->business_location_id = $locationId;

        return $user;
    }

    /**
     * Create a sale with one item linked to the warehouse.
     */
    protected function createSaleWithItem(int $businessId, int $userId, int $warehouseId): int
    {
        $productId = DB::table('products')->insertGetId([
            'business_id'     => $businessId,
            'name'            => 'Test Product',
            'sku'             => uniqid('SKU-'),
            'cost_price'      => 500,
            'selling_price'   => 1000,
            'track_inventory' => true,
            'active'          => true,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $saleId = DB::table('sales')->insertGetId([
            'business_id'          => $businessId,
            'business_location_id' => DB::table('business_locations')
                                        ->where('business_id', $businessId)
                                        ->value('id'),
            'warehouse_id'         => $warehouseId,
            'sale_number'          => uniqid('SALE-'),
            'created_by'           => $userId,
            'total'                => 1000,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        DB::table('sale_items')->insert([
            'sale_id'      => $saleId,
            'product_id'   => $productId,
            'quantity'     => 1,
            'unit_price'   => 1000,
            'total_price'  => 1000,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return $saleId;
    }
}
