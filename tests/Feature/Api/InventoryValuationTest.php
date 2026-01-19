<?php

namespace Tests\Feature\Api;

use App\Models\Business;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\StockBatch;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryValuationTest extends TestCase
{
    use RefreshDatabase;

    public function test_multi_location_and_in_transit_valuation()
    {
        // 1. Setup Environment
        $business = Business::factory()->create();
        $user = User::factory()->create(['business_id' => $business->id]);
        $warehouse = Warehouse::factory()->create(['business_id' => $business->id, 'name' => 'Main Warehouse']);
        $stall = Warehouse::factory()->create(['business_id' => $business->id, 'name' => 'Kano Market Stall']);
        $product = Product::factory()->create(['business_id' => $business->id]);

        // 2. Create Required Parent Records (To satisfy foreign key/non-null constraints)
        DB::table('parties')->insert([
            'id' => 1, 
            'business_id' => $business->id, 
            'name' => 'Test Supplier', 
            'type' => 'supplier'
        ]);

        DB::table('purchases')->insert([
            'id' => 1, 
            'business_id' => $business->id, 
            'warehouse_id' => $warehouse->id, 
            'supplier_id' => 1, 
            'purchase_number' => 'PUR-001', 
            'created_by' => $user->id
        ]);

        $this->actingAs($user);

        // 3. Create Stock: 10 units @ 1,000 NGN (Total 10,000)
        StockBatch::create([
            'business_id' => $business->id,
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'purchase_id' => 1, // <--- Now provided
            'quantity_received' => 10,
            'quantity_remaining' => 10,
            'unit_cost' => 1000.00,
            'received_at' => now()->subDays(2)
        ]);

        // 4. Dispatch 4 units to the Stall (This puts 4,000 NGN "In-Transit")
        $this->postJson('/api/v1/stock-transfers/dispatch', [
            'from_warehouse_id' => $warehouse->id,
            'to_warehouse_id' => $stall->id,
            'items' => [['product_id' => $product->id, 'quantity' => 4]]
        ]);

        // 5. Request the Valuation Report
        $response = $this->getJson('/api/v1/inventory/valuation');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        // 6. Assert Logic: 
        // Warehouse should have 6 units left (6,000 NGN)
        // In-Transit should have 4 units (4,000 NGN)
        $locations = collect($response->json('data.by_location'));
        $mainWhValuation = $locations->where('warehouse_id', $warehouse->id)->first();

        $this->assertEquals(6000.00, $mainWhValuation['total_value']);
        $this->assertEquals(4000.00, $response->json('data.in_transit.transit_value'));
    }
}