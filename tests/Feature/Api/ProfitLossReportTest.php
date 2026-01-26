<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Business;
use App\Models\BusinessLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Helpers\SeedsLedger;
use App\Services\SaleService;

class ProfitLossReportTest extends TestCase
{
    use RefreshDatabase, SeedsLedger;

    /** @test */
    public function it_calculates_correct_profit_and_loss_via_api()
    {
        // --- 1. SETUP ENVIRONMENT ---
        $business = Business::factory()->create(['valuation_method' => 'fifo']);
        $user = User::factory()->create(['business_id' => $business->id]);
        $location = BusinessLocation::factory()->create(['business_id' => $business->id]);
        $warehouse = Warehouse::factory()->create(['business_id' => $business->id]);
        $product = Product::factory()->create(['business_id' => $business->id]);

        $this->actingAs($user); 
        
        // --- 2. SEED MANDATORY LEDGER ---
        // This prevents ModelNotFoundException for 'Cash at Hand' and other system accounts
        $this->seedLedgerForBusiness($business);
        
        // Initialize Stock: 10 units @ 700.00 NGN cost
        // This establishes the FIFO layers for COGS calculation
        $this->initializeProductStock($business->id, $warehouse->id, $product->id, $user->id, 10, 700.00);

        // --- 3. EXECUTE SALE ---
        $saleData = [
            'business_id'          => $business->id,
            'business_location_id' => $location->id,
            'warehouse_id'         => $warehouse->id,
            'sale_number'          => 'SALE-TEST-001',
            'subtotal'             => 1000.00,
            'total'                => 1000.00,
            'status'               => 'final', // Explicitly set status to trigger ledger posting
            'created_by'           => $user->id,
        ];

        $items = [
            [
                'product_id' => $product->id, 
                'quantity'   => 1, 
                'unit_price' => 1000.00
            ]
        ];

        app(SaleService::class)->create($saleData, $items);

        // --- 4. VERIFY REPORTING ENGINE ---
        $today = now()->toDateString();
        $response = $this->getJson("/api/v1/reports/profit-loss?start_date={$today}&end_date={$today}");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.revenue', 1000)
            ->assertJsonPath('data.cogs', 700)
            ->assertJsonPath('data.gross_profit', 300);
    }
}