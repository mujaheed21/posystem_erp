<?php

namespace App\Services;

use App\Models\OfflineFulfillmentPending;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OfflineFulfillmentStateMachine
{
    /**
     * Allowed STATE transitions.
     */
    private const TRANSITIONS = [
        'pending'  => ['approved', 'rejected'],
        'approved' => ['reconciled'],
    ];

    /**
     * Transition offline fulfillment STATE.
     *
     * @param  OfflineFulfillmentPending  $pending
     * @param  string                     $toState
     * @param  int|null                   $userId
     * @param  array                      $metadata
     */
    public static function transition(
        OfflineFulfillmentPending $pending,
        string $toState,
        ?int $userId = null,
        array $metadata = []
    ): OfflineFulfillmentPending {
        return DB::transaction(function () use ($pending, $toState, $userId, $metadata) {

            $locked = OfflineFulfillmentPending::lockForUpdate()
                ->findOrFail($pending->id);

            $currentState = $locked->state;

            // Terminal states
            if (in_array($currentState, ['reconciled', 'rejected'], true)) {
                throw new RuntimeException('offline_fulfillment_finalized');
            }

            $allowed = self::TRANSITIONS[$currentState] ?? [];

            if (! in_array($toState, $allowed, true)) {
                throw new RuntimeException(
                    "invalid_offline_transition {$currentState} → {$toState}"
                );
            }

            // 🔒 SINGLE SOURCE OF TRUTH
            $locked->state = $toState;

            if ($toState === 'approved') {
                $locked->approved_by = $userId;
                $locked->approved_at = now();
            }

            if ($toState === 'reconciled') {
                $locked->fulfilled_at = now();

                // ✅ SINGLE authoritative audit log with metadata
                AuditService::log(
                    'offline_fulfillment_reconciled',
                    'offline_fulfillment',
                    OfflineFulfillmentPending::class,
                    $locked->id,
                    $metadata
                );
            }

            $locked->save();

            return $locked;
        });
    }

    /**
     * Reject offline fulfillment (terminal).
     */
    public static function reject(
        OfflineFulfillmentPending $pending,
        string $reason,
        int $userId
    ): void {
        DB::transaction(function () use ($pending, $reason, $userId) {

            $locked = OfflineFulfillmentPending::lockForUpdate()
                ->findOrFail($pending->id);

            if (in_array($locked->state, ['reconciled', 'rejected'], true)) {
                return;
            }

            $locked->state = 'rejected';
            $locked->rejected_reason = $reason;
            $locked->approved_by = $userId;
            $locked->approved_at = now();

            $locked->save();
        });
    }

    /**
     * Conflict handler (NON-PERSISTENT).
     */
    public static function conflict(
        OfflineFulfillmentPending $pending,
        string $reason
    ): void {
        AuditService::log(
            'offline_fulfillment_conflict',
            'offline_fulfillment',
            OfflineFulfillmentPending::class,
            $pending->id,
            ['reason' => $reason]
        );
    }
}
