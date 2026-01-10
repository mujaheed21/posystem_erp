<?php

namespace App\Services;

use App\Models\OfflineFulfillmentPending;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OfflineReconciliationService
{
    /**
     * Approve offline fulfillment.
     */
    public function approve(int $pendingId): void
    {
        $user = Auth::user();

        DB::transaction(function () use ($pendingId, $user) {

            $pending = OfflineFulfillmentPending::lockForUpdate()
                ->findOrFail($pendingId);

            if ($pending->state !== 'pending') {
                throw ValidationException::withMessages([
                    'state' => 'Offline fulfillment not pending.',
                ]);
            }

            OfflineFulfillmentStateMachine::transition(
                $pending,
                'approved',
                $user->id
            );
        });
    }

    /**
     * Reconcile an approved offline fulfillment.
     */
    public function reconcile(int $pendingId): void
    {
        $user = Auth::user();

        DB::transaction(function () use ($pendingId, $user) {

            $pending = OfflineFulfillmentPending::lockForUpdate()
                ->findOrFail($pendingId);

            if ($pending->state !== 'approved') {
                throw ValidationException::withMessages([
                    'state' => 'Offline fulfillment not approved.',
                ]);
            }

            if ($pending->fulfilled_at !== null) {
                throw ValidationException::withMessages([
                    'state' => 'Offline fulfillment already reconciled.',
                ]);
            }

            /**
             * ✅ STATE FIRST (authority)
             */
            OfflineFulfillmentStateMachine::transition(
                $pending,
                'reconciled',
                $user->id
            );

            /**
             * ✅ SIDE EFFECTS AFTER STATE
             */
            app(FulfillmentService::class)->fulfillOffline($pending);
        });
    }
}
