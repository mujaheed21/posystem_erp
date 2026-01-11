<?php

namespace Tests\Feature\Fulfillment;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\OfflineFulfillmentPending;
use App\Models\SupervisorOverride;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\Helpers\FulfillmentTestHelper;
use App\Services\OfflineFulfillmentStateMachine;

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

        $this->postJson(
            "/api/offline-fulfillments/{$pending->id}/approve"
        )->assertOk();

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

    /** @test */
    public function reconciliation_fails_when_supervisor_override_is_required_but_missing()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $pending = OfflineFulfillmentPending::factory()->create([
            'state'             => 'approved',
            'requires_override' => true,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'supervisor_override_required_for_reconciliation'
        );

        OfflineFulfillmentStateMachine::transition(
            $pending,
            'reconciled',
            $user->id,
            []
        );
    }

    /** @test */
    public function supervisor_override_cannot_be_used_for_wrong_offline_fulfillment()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $pendingA = OfflineFulfillmentPending::factory()->create([
            'state'             => 'approved',
            'requires_override' => true,
        ]);

        $pendingB = OfflineFulfillmentPending::factory()->create([
            'state'             => 'approved',
            'requires_override' => true,
        ]);

        $override = SupervisorOverride::factory()->create([
            'target_type' => OfflineFulfillmentPending::class,
            'target_id'   => $pendingA->id,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'supervisor_override_target_mismatch'
        );

        OfflineFulfillmentStateMachine::transition(
            $pendingB,
            'reconciled',
            $user->id,
            [
                'supervisor_override_id' => $override->id,
            ]
        );
    }
}
