<?php

namespace App\Services;

use App\Models\WarehouseFulfillment;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FulfillmentStateMachine
{
    /**
     * Allowed state transitions.
     */
    private const TRANSITIONS = [
        'pending'   => ['approved'],
        'approved'  => ['released'],
        'released'  => ['reconciled'],
    ];

    /**
     * Transition fulfillment to a new state with guards.
     */
    public static function transition(
        WarehouseFulfillment $fulfillment,
        string $toState
    ): WarehouseFulfillment {
        return DB::transaction(function () use ($fulfillment, $toState) {

            // Reload with row lock for race safety
            $locked = WarehouseFulfillment::where('id', $fulfillment->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Terminal states cannot transition
            if (in_array($locked->state, ['reconciled', 'conflicted'], true)) {
                throw new RuntimeException('Fulfillment already finalized');
            }

            // Validate transition
            $allowed = self::TRANSITIONS[$locked->state] ?? [];

            if (!in_array($toState, $allowed, true)) {
                throw new RuntimeException(
                    "Invalid transition {$locked->state} → {$toState}"
                );
            }

            // Optimistic version bump
            $locked->state = $toState;
            $locked->version++;
            $locked->save();

            return $locked;
        });
    }

    /**
     * Force conflict (terminal).
     */
    public static function conflict(
        WarehouseFulfillment $fulfillment,
        string $reason
    ): void {
        DB::transaction(function () use ($fulfillment, $reason) {

            $locked = WarehouseFulfillment::where('id', $fulfillment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($locked->state, ['reconciled', 'conflicted'], true)) {
                return;
            }

            $locked->state = 'conflicted';
            $locked->version++;
            $locked->save();

            AuditService::log(
                action: 'fulfillment_conflicted',
                auditable: $locked,
                metadata: ['reason' => $reason]
            );
        });
    }
}
