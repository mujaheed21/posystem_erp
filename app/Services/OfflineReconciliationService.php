<?php

namespace App\Services;

use App\Models\OfflineFulfillmentPending;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\AuditService;

class OfflineReconciliationService
{
    public function approve(int $pendingId, User $supervisor): void
    {
        $pending = OfflineFulfillmentPending::lockForUpdate()->findOrFail($pendingId);

        if ($pending->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'Offline fulfillment is not pending.',
            ]);
        }

        $pending->update([
            'status' => 'approved',
            'approved_by' => $supervisor->id,
            'approved_at' => now(),
        ]);
    }

    public function reject(int $pendingId, User $supervisor, string $reason): void
    {
        $pending = OfflineFulfillmentPending::lockForUpdate()->findOrFail($pendingId);

        if ($pending->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'Offline fulfillment cannot be rejected.',
            ]);
        }

        $pending->update([
            'status' => 'rejected',
            'approved_by' => $supervisor->id,
            'approved_at' => now(),
            'rejected_reason' => $reason,
        ]);
    }

    public function reconcile(int $pendingId, User $supervisor): void
    {
        DB::transaction(function () use ($pendingId, $supervisor) {
            $pending = OfflineFulfillmentPending::lockForUpdate()->findOrFail($pendingId);

            if ($pending->status !== 'approved') {
                throw ValidationException::withMessages([
                    'status' => 'Offline fulfillment not approved.',
                ]);
            }

            app(FulfillmentService::class)->fulfillOffline($pending);

            $pending->update([
                'status' => 'fulfilled',
                'fulfilled_at' => now(),
            ]);

           AuditService::log(
            action: 'offline_fulfillment_reconciled',
            module: 'fulfillment',
            auditableType: 'offline_fulfillment_pending',
            auditableId: $pending->id,
            metadata: ['pending_id' => $pending->id]
        );

        });
    }
}