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

class StockAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_low_stock_alert_triggers_correctly()
    {
        // 1. Setup
        $business = Business::factory()->create();
        $user = User::factory()->create(['business_id' => $business->id]);
        $warehouse = Warehouse::factory()->create(['business_id' => $business->id]);
        $product = Product::factory()->create(['business_id' => $business->id, 'name' => 'Dangote Sugar']);

        // Satisfy foreign key requirements for batches
        DB::table('parties')->insert(['id' => 1, 'business_id' => $business->id, 'name' => 'Supplier', 'type' => 'supplier']);
        DB::table('purchases')->insert(['id' => 1, 'business_id' => $business->id, 'warehouse_id' => $warehouse->id, 'supplier_id' => 1, 'purchase_number' => 'P1', 'created_by' => $user->id]);

        // 2. Set a Threshold: Alert if stock <= 5
        DB::table('stock_thresholds')->insert([
            'business_id' => $business->id,
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'min_level' => 5.000,
            'reorder_qty' => 20.000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Add 10 units of stock (No alert should trigger yet)
        StockBatch::create([
            'business_id' => $business->id, 'warehouse_id' => $warehouse->id, 'product_id' => $product->id,
            'purchase_id' => 1, 'quantity_received' => 10, 'quantity_remaining' => 10, 'unit_cost' => 500, 'received_at' => now()
        ]);

        $this->actingAs($user);

        // Verify alert is empty initially
        $response = $this->getJson('/api/v1/inventory/alerts');
        $response->assertStatus(200)->assertJsonCount(0, 'data');

        // 4. Adjust stock down by 6 units (Remaining: 4)
        // This should cross the threshold of 5
        $this->postJson('/api/v1/inventory/adjust', [
            'warehouse_id' => $warehouse->id,
            'type' => 'damage',
            'items' => [['product_id' => $product->id, 'quantity' => 6]]
        ]);

        // 5. Check Alerts API again
        $response = $this->getJson('/api/v1/inventory/alerts');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.product_name', 'Dangote Sugar')
            ->assertJsonPath('data.0.current_stock', "4.000");
    }
}