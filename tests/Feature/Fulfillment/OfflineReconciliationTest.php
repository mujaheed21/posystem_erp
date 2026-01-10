<?php

namespace Tests\Feature\Fulfillment;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\OfflineFulfillmentPending;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\Helpers\FulfillmentTestHelper;

class OfflineReconciliationTest extends TestCase
{
    use RefreshDatabase;
    use FulfillmentTestHelper;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('offline.fulfillment.approve');
        Permission::findOrCreate('warehouse.fulfill');
    }

    /** @test */
    public function normal_user_cannot_approve_offline_fulfillment()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $pending = $this->createOfflinePending();

        $this->postJson(
            "/api/offline-fulfillments/{$pending->id}/approve"
        )->assertForbidden();
    }

    /** @test */
    public function offline_pending_cannot_be_reconciled_without_approval()
    {
        $supervisor = User::factory()->create();
        $supervisor->givePermissionTo('offline.fulfillment.approve');

        Sanctum::actingAs($supervisor);

        $pending = $this->createOfflinePending();

        $this->postJson(
            "/api/offline-fulfillments/{$pending->id}/reconcile"
        )->assertStatus(422);
    }

    /** @test */
    public function rejected_offline_pending_cannot_be_reconciled()
    {
        $supervisor = User::factory()->create();
        $supervisor->givePermissionTo('offline.fulfillment.approve');

        Sanctum::actingAs($supervisor);

        $pending = $this->createOfflinePending();

        $this->postJson(
            "/api/offline-fulfillments/{$pending->id}/reject",
            ['reason' => 'Invalid payload']
        );

        $this->postJson(
            "/api/offline-fulfillments/{$pending->id}/reconcile"
        )->assertStatus(422);
    }

    /** @test */
    public function approved_offline_pending_can_be_reconciled()
    {
        $supervisor = User::factory()->create();
        $supervisor->givePermissionTo('offline.fulfillment.approve');
        $supervisor->givePermissionTo('warehouse.fulfill');

        Sanctum::actingAs($supervisor);

        [$pending, $product, $warehouse] = $this->createOfflinePendingWithStock();

        // APPROVE
        $this->postJson(
            "/api/offline-fulfillments/{$pending->id}/approve"
        )->assertOk();

        /**
         * 🔥 SMOKING GUN 🔥
         * If this fails, approval never persisted.
         */
        $this->assertDatabaseHas('offline_fulfillment_pendings', [
            'id'     => $pending->id,
            'state' => 'approved',
        ]);

        // RECONCILE
        $this->postJson(
            "/api/offline-fulfillments/{$pending->id}/reconcile"
        )->assertOk();

        $this->assertDatabaseHas('warehouse_stock', [
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'quantity'     => 9,
        ]);
    }

    /** @test */
    public function reconciliation_cannot_happen_twice()
    {
        $supervisor = User::factory()->create();
        $supervisor->givePermissionTo('offline.fulfillment.approve');
        $supervisor->givePermissionTo('warehouse.fulfill');

        Sanctum::actingAs($supervisor);

        [$pending] = $this->createOfflinePendingWithStock();

        $this->postJson("/api/offline-fulfillments/{$pending->id}/approve");
        $this->postJson("/api/offline-fulfillments/{$pending->id}/reconcile");

        $this->postJson(
            "/api/offline-fulfillments/{$pending->id}/reconcile"
        )->assertStatus(422);
    }

    /** @test */
    public function reconciliation_writes_audit_log()
    {
        $supervisor = User::factory()->create();
        $supervisor->givePermissionTo('offline.fulfillment.approve');
        $supervisor->givePermissionTo('warehouse.fulfill');

        Sanctum::actingAs($supervisor);

        [$pending] = $this->createOfflinePendingWithStock();

        $this->postJson("/api/offline-fulfillments/{$pending->id}/approve");
        $this->postJson("/api/offline-fulfillments/{$pending->id}/reconcile");

        $this->assertDatabaseHas('audit_logs', [
            'action'  => 'offline_fulfillment_reconciled',
            'user_id' => $supervisor->id,
        ]);
    }
}
