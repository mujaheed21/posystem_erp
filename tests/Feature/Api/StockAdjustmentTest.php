<?php

namespace Tests\Feature\Api;

use App\Models\Business;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\StockBatch;
use App\Models\User;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockAdjustmentTest extends TestCase
{
    use RefreshDatabase;
    public function test_fifo_stock_adjustment_logic()
    {
        // 1. Setup Environment
        $business = Business::factory()->create();
        $user = User::factory()->create(['business_id' => $business->id]);
        $warehouse = Warehouse::factory()->create(['business_id' => $business->id]);
        $product = Product::factory()->create(['business_id' => $business->id]);

        // Setup Parent Data for foreign key constraints
        DB::table('parties')->insert(['id' => 1, 'business_id' => $business->id, 'name' => 'Supplier', 'type' => 'supplier']);
        DB::table('purchases')->insert(['id' => 1, 'business_id' => $business->id, 'warehouse_id' => $warehouse->id, 'supplier_id' => 1, 'purchase_number' => 'P1', 'created_by' => $user->id]);

        // 2. Create 2 Batches (FIFO check)
        // Oldest Batch: 10 units @ 500 NGN
        $oldBatch = StockBatch::create([
            'business_id' => $business->id, 'warehouse_id' => $warehouse->id, 'product_id' => $product->id,
            'purchase_id' => 1, 'quantity_received' => 10, 'quantity_remaining' => 10, 'unit_cost' => 500.00, 'received_at' => now()->subDays(5)
        ]);

        // Newer Batch: 10 units @ 600 NGN
        $newBatch = StockBatch::create([
            'business_id' => $business->id, 'warehouse_id' => $warehouse->id, 'product_id' => $product->id,
            'purchase_id' => 1, 'quantity_received' => 10, 'quantity_remaining' => 10, 'unit_cost' => 600.00, 'received_at' => now()->subDays(1)
        ]);

        $this->actingAs($user);

        // 3. Adjust 12 units (Should empty oldBatch and take 2 from newBatch)
        $response = $this->postJson('/api/v1/inventory/adjust', [
            'warehouse_id' => $warehouse->id,
            'type' => 'damage',
            'notes' => 'Damaged by rain in Kano warehouse',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 12]
            ]
        ]);

        $response->assertStatus(200);

        // 4. Verify Batch Quantities
        $this->assertDatabaseHas('stock_batches', [
            'id' => $oldBatch->id,
            'quantity_remaining' => 0
        ]);

        $this->assertDatabaseHas('stock_batches', [
            'id' => $newBatch->id,
            'quantity_remaining' => 8.000
        ]);

        // 5. Verify Ledger (StockMovement)
        // Check for the 'adjustment' type entry we found in your DB dump
        $this->assertDatabaseHas('stock_movements', [
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'type' => 'adjustment',
            'reference_id' => $oldBatch->id,
            'quantity' => 10
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'type' => 'adjustment',
            'reference_id' => $newBatch->id,
            'quantity' => 2
        ]);
    }

    public function test_adjustment_fails_on_insufficient_stock()
    {
        $business = Business::factory()->create();
        $user = User::factory()->create(['business_id' => $business->id]);
        $warehouse = Warehouse::factory()->create(['business_id' => $business->id]);
        $product = Product::factory()->create(['business_id' => $business->id]);

        $this->actingAs($user);

        // Attempt to adjust stock that doesn't exist
        $response = $this->postJson('/api/v1/inventory/adjust', [
            'warehouse_id' => $warehouse->id,
            'type' => 'theft',
            'items' => [['product_id' => $product->id, 'quantity' => 100]]
        ]);

        $response->assertStatus(400);
        $response->assertJsonPath('status', 'error');
    }
}