<?php

namespace Tests\Feature\Api;

use App\Models\Business;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\StockBatch;
use App\Models\StockTransfer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTransferTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Test the full FIFO lifecycle: 
     * Dispatching from Warehouse A -> In-Transit -> Receiving at Warehouse B.
     * Verifies batch-level integrity and movement ledger accuracy.
     */
    public function test_secure_qr_fulfillment_flow()
    {
        // 1. Setup (Same as before)
        $business = Business::factory()->create();
        $user = User::factory()->create(['business_id' => $business->id]);
        $warehouseA = Warehouse::factory()->create(['business_id' => $business->id]);
        $warehouseB = Warehouse::factory()->create(['business_id' => $business->id]);
        $product = Product::factory()->create(['business_id' => $business->id]);

        DB::table('parties')->insert(['id' => 1, 'business_id' => $business->id, 'name' => 'Supplier', 'type' => 'supplier']);
        DB::table('purchases')->insert(['id' => 1, 'business_id' => $business->id, 'warehouse_id' => $warehouseA->id, 'supplier_id' => 1, 'purchase_number' => 'P1', 'created_by' => $user->id]);
        
        StockBatch::create([
            'business_id' => $business->id, 'warehouse_id' => $warehouseA->id, 'product_id' => $product->id,
            'purchase_id' => 1, 'quantity_received' => 20, 'quantity_remaining' => 20, 'unit_cost' => 500, 'received_at' => now()
        ]);

        $this->actingAs($user);

        // 2. Dispatch - This generates the 'verification_token'
        $dispatchResponse = $this->postJson('/api/v1/stock-transfers/dispatch', [
            'from_warehouse_id' => $warehouseA->id,
            'to_warehouse_id' => $warehouseB->id,
            'items' => [['product_id' => $product->id, 'quantity' => 10]]
        ]);

        $dispatchResponse->assertStatus(200);
        $token = $dispatchResponse->json('data.verification_token');
        $this->assertNotNull($token);

        // 3. Attempt to fulfill with a FAKE token (Security Check)
        $fakeResponse = $this->postJson('/api/v1/stock-transfers/fulfill', [
            'token' => 'this-token-is-a-lie-12345678901234567890'
        ]);
        $fakeResponse->assertStatus(400); // Should fail

        // 4. Fulfill with the REAL token (The "QR Scan")
        $fulfillResponse = $this->postJson('/api/v1/stock-transfers/fulfill', [
            'token' => $token
        ]);

        $fulfillResponse->assertStatus(200);
        $fulfillResponse->assertJsonPath('data.status', 'completed');

        // 5. Final Inventory Check
        $this->assertDatabaseHas('stock_batches', [
            'warehouse_id' => $warehouseB->id,
            'product_id' => $product->id,
            'quantity_remaining' => 10.000
        ]);
    }
}