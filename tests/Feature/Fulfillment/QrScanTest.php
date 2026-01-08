<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\Helpers\FulfillmentTestHelper;
use App\Services\FulfillmentTokenService;
use PHPUnit\Framework\Attributes\Test;
use App\Models\WarehouseStock;

class QrScanTest extends TestCase
{
    use FulfillmentTestHelper;

    #[Test]
    public function it_requires_authentication(): void
    {
        $this->postJson('/api/fulfillments/scan', [
            'token' => 'any',
        ])->assertStatus(401);
    }

    #[Test]
    public function authenticated_user_without_permission_is_denied(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/fulfillments/scan', [
            'token' => 'any',
        ])->assertStatus(403);
    }

    #[Test]
    public function user_with_warehouse_fulfill_permission_can_reach_business_logic(): void
    {
        $user = User::factory()->create();

        Permission::firstOrCreate([
            'name' => 'warehouse.fulfill',
            'guard_name' => 'web',
        ]);

        $user->givePermissionTo('warehouse.fulfill');
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/fulfillments/scan', [
            'token' => 'dummy',
        ]);

        $this->assertContains(
            $response->status(),
            [200, 409, 410, 422]
        );
    }

    #[Test]
    public function valid_fulfillment_token_is_approved(): void
    {
        $user = $this->setupWarehouseUser();

        $saleId = $this->createSaleWithItem(
            $user->business_id,
            $user->id,
            $user->warehouse_id
        );

        $token = FulfillmentTokenService::generate(
            $saleId,
            $user->warehouse_id,
            30
        );

        $saleItem = DB::table('sale_items')
            ->where('sale_id', $saleId)
            ->first();

        WarehouseStock::updateOrCreate(
            [
                'warehouse_id' => $user->warehouse_id,
                'product_id'   => $saleItem->product_id,
            ],
            [
                'quantity' => 100,
            ]
        );




        $this->postJson('/api/fulfillments/scan', [
            'token' => $token,
        ])
        ->assertStatus(200)
        ->assertJson(['status' => 'approved']);
    }

    #[Test]
    public function reused_fulfillment_token_is_rejected(): void
    {
        $user = $this->setupWarehouseUser();

        $saleId = $this->createSaleWithItem(
            $user->business_id,
            $user->id,
            $user->warehouse_id
        );

        $token = FulfillmentTokenService::generate(
            $saleId,
            $user->warehouse_id,
            30
        );

        $saleItem = DB::table('sale_items')
            ->where('sale_id', $saleId)
            ->first();


        // First scan → approved
        $this->postJson('/api/fulfillments/scan', [
            'token' => $token,
        ])->assertStatus(200);

        // Second scan → rejected
        $this->postJson('/api/fulfillments/scan', [
            'token' => $token,
        ])->assertStatus(409);
    }

    #[Test]
    public function expired_fulfillment_token_is_rejected(): void
    {
        $user = $this->setupWarehouseUser();

        $saleId = $this->createSaleWithItem(
            $user->business_id,
            $user->id,
            $user->warehouse_id
        );

        $token = FulfillmentTokenService::generate(
            $saleId,
            $user->warehouse_id,
            -1
        );

        $this->postJson('/api/fulfillments/scan', [
            'token' => $token,
        ])->assertStatus(410);
    }
}
