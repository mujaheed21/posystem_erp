<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Business;
use App\Models\BusinessLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\DB;

class ProfitLossReportTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
   public function it_calculates_correct_profit_and_loss_via_api()
    {
        $business = Business::factory()->create(['valuation_method' => 'fifo']);
        $user = User::factory()->create(['business_id' => $business->id]);
        $location = BusinessLocation::factory()->create(['business_id' => $business->id]);
        $warehouse = Warehouse::factory()->create(['business_id' => $business->id]);
        $product = Product::factory()->create(['business_id' => $business->id]);

        $this->actingAs($user); // Sanctum auth
        
        $this->setupBusinessLedger($business->id);
        
        // Pass $user->id as the 4th argument
        $this->initializeProductStock($business->id, $warehouse->id, $product->id, $user->id, 10, 700.00);

        $saleData = [
            'business_id' => $business->id,
            'business_location_id' => $location->id,
            'warehouse_id' => $warehouse->id,
            'sale_number' => 'SALE-TEST-001',
            'subtotal' => 1000.00,
            'total' => 1000.00,
        ];
        $items = [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 1000.00]];
        app(\App\Services\SaleService::class)->create($saleData, $items);

        $today = now()->toDateString();
        $response = $this->getJson("/api/v1/reports/profit-loss?start_date={$today}&end_date={$today}");

        $response->assertStatus(200)
            ->assertJsonPath('data.revenue', 1000)
            ->assertJsonPath('data.cogs', 700);
    }
}