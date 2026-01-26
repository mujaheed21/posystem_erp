<?php

namespace Tests\Feature\Fulfillment;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Business;
use App\Models\Warehouse;
use App\Models\Sale;
use App\Models\Product;
use App\Models\FulfillmentToken;
use App\Models\OfflineFulfillmentPending;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\Helpers\FulfillmentTestHelper;
use Tests\Helpers\SeedsLedger;
use Illuminate\Support\Str;

class QrScanTest extends TestCase
{
    use RefreshDatabase, FulfillmentTestHelper, SeedsLedger;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::findOrCreate('warehouse.fulfill', 'sanctum');
    }

    /** @test */
    public function valid_fulfillment_token_is_reconciled()
    {
        $business = Business::factory()->create();
        $user = User::factory()->create(['business_id' => $business->id]);
        $user->syncPermissions([Permission::findByName('warehouse.fulfill', 'sanctum')]);
        Sanctum::actingAs($user);

        $warehouse = Warehouse::factory()->create(['business_id' => $business->id]);
        $product = Product::factory()->create(['business_id' => $business->id]);

        // 1. 🛑 FIX: Seed the Warehouse Stock (Required for the commit phase)
        DB::table('warehouse_stock')->insert([
            'warehouse_id'      => $warehouse->id,
            'product_id'        => $product->id,
            'quantity'          => 10.00,
            'reserved_quantity' => 1.00, // Matching the quantity of the sale
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $sale = Sale::factory()->create([
            'business_id'  => $business->id,
            'warehouse_id' => $warehouse->id,
            'created_by'   => $user->id, 
            'status'       => 'completed'
        ]);

        // 2. Seed Sale Items
        DB::table('sale_items')->insert([
            'sale_id'     => $sale->id,
            'product_id'  => $product->id,
            'quantity'    => 1.00,
            'unit_price'  => 100,
            'total_price' => 100,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // 3. Create the State Machine record
        $pending = OfflineFulfillmentPending::factory()->create([
            'sale_id'      => $sale->id,
            'warehouse_id' => $warehouse->id,
            'state'        => 'approved',
            'payload'      => ['items' => [['product_id' => $product->id, 'quantity' => 1]]],
        ]);

        // 4. Generate identical hash to FulfillmentService
        $itemsFromDb = DB::table('sale_items')
            ->where('sale_id', $sale->id)
            ->orderBy('product_id', 'asc')
            ->get(['product_id', 'quantity']);

        $dataToHash = $itemsFromDb->map(fn ($i) => [
            'product_id' => (int) $i->product_id,
            'quantity'   => (string) number_format((float) $i->quantity, 2, '.', ''),
        ])->values()->all();

        $itemsHash = hash('sha256', json_encode($dataToHash, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $rawToken = 'QR-' . Str::random(32);
        
        FulfillmentToken::create([
            'token_hash'   => hash('sha256', $rawToken), 
            'items_hash'   => $itemsHash,
            'sale_id'      => $sale->id,
            'warehouse_id' => $warehouse->id,
            'expires_at'   => now()->addHours(24),
            'used'         => 0,
        ]);

        // 5. Hit endpoint
        $response = $this->postJson('/api/v1/fulfillments/scan', [
            'token' => $rawToken
        ]);

        if ($response->status() !== 200) {
            dump("Error Response: ", $response->json());
        }

        $response->assertOk();
        
        // 6. Assertions: State should be terminal and Stock should be deducted
        $this->assertDatabaseHas('warehouse_fulfillments', [
            'sale_id' => $sale->id,
            'state'   => 'reconciled'
        ]);

        $this->assertDatabaseHas('warehouse_stock', [
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'quantity'     => 9.00 // 10.00 - 1.00
        ]);
    }

    /** @test */
    public function reused_fulfillment_token_is_rejected()
    {
        $business = Business::factory()->create();
        $warehouse = Warehouse::factory()->create(['business_id' => $business->id]);
        $user = User::factory()->create(['business_id' => $business->id]);
        $user->syncPermissions([Permission::findByName('warehouse.fulfill', 'sanctum')]);
        Sanctum::actingAs($user);

        $sale = Sale::factory()->create(['business_id' => $business->id]);
        $rawToken = 'USED-QR-' . Str::random(16);
        
        FulfillmentToken::create([
            'token_hash'   => hash('sha256', $rawToken),
            'sale_id'      => $sale->id,
            'warehouse_id' => $warehouse->id, 
            'expires_at'   => now()->addHours(24),
            'used'         => 1,
            'items_hash'   => 'dummy-hash'
        ]);

        $this->postJson('/api/v1/fulfillments/scan', [
            'token' => $rawToken
        ])->assertStatus(409);
    }

    /** @test */
    public function expired_fulfillment_token_is_rejected()
    {
        $business = Business::factory()->create();
        $warehouse = Warehouse::factory()->create(['business_id' => $business->id]);
        $user = User::factory()->create(['business_id' => $business->id]);
        $user->syncPermissions([Permission::findByName('warehouse.fulfill', 'sanctum')]);
        Sanctum::actingAs($user);

        $sale = Sale::factory()->create(['business_id' => $business->id]);
        $rawToken = 'EXPIRED-QR-' . Str::random(16);
        
        FulfillmentToken::create([
            'token_hash'   => hash('sha256', $rawToken),
            'sale_id'      => $sale->id,
            'warehouse_id' => $warehouse->id,
            'expires_at'   => now()->subDays(1),
            'used'         => 0,
            'items_hash'   => 'dummy-hash'
        ]);

        $this->postJson('/api/v1/fulfillments/scan', [
            'token' => $rawToken
        ])->assertStatus(410);
    }
}