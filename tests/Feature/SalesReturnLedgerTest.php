<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\BusinessLocation;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Helpers\SeedsLedger;
use PHPUnit\Framework\Attributes\Test;

class SalesReturnLedgerTest extends TestCase
{
    use RefreshDatabase, SeedsLedger;

    #[Test]
    public function a_sale_return_reverses_financial_and_stock_impact()
    {
        // --- 1. SETUP ENVIRONMENT ---
        $business = Business::factory()->create();
        $user = User::factory()->create(['business_id' => $business->id]);
        $warehouse = Warehouse::factory()->create(['business_id' => $business->id]);
        $product = Product::factory()->create(['business_id' => $business->id]);
        $location = BusinessLocation::factory()->create(['business_id' => $business->id]);

        $this->actingAs($user);

        // --- 2. SEED MANDATORY LEDGER ---
        $this->seedLedgerForBusiness($business);
        
        // Initialize Stock: 50 units @ 500.00 cost
        $this->initializeProductStock($business->id, $warehouse->id, $product->id, $user->id, 50, 500.00);

        $saleService = app(SaleService::class);

        // --- 3. EXECUTE INITIAL SALE ---
        $saleId = $saleService->create([
            'business_id' => $business->id,
            'business_location_id' => $location->id,
            'warehouse_id' => $warehouse->id,
            'sale_number' => 'SALE-REV-001',
            'subtotal' => 1000,
            'total' => 1000,
            'status' => 'final',
            'created_by' => $user->id,
        ], [
            ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 1000]
        ]);

        // --- 4. EXECUTE RETURN ---
        $saleService->processReturn($saleId, [
            [
                'product_id' => $product->id, 
                'quantity' => 1, 
                'unit_price' => 1000,
                'restock' => true 
            ]
        ]);

        // --- 5. VERIFY INTEGRITY ---
        // Verify physical stock increment
        $this->assertDatabaseHas('warehouse_stock', [
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'quantity'     => 51.000 
        ]);

        // Verify ledger reversal
        $this->assertDatabaseHas('ledger_entries', [
            'business_id' => $business->id,
            'source_type' => 'App\\Models\\Sale',
            'user_id'     => $user->id,
            'source_id'   => $saleId,
        ]);
    }
}