<?php

namespace App\Services;

use App\Models\SupervisorOverride;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class SupervisorOverrideService
{
    /**
     * Persist a supervisor override as an append-only security event.
     */
    public static function issue(
        string $attestationToken,
        string $eventType,
        string $targetType,
        string $targetId,
        string $reasonCode,
        string $reasonText,
        array $authFactors,
        string $deviceFingerprint
    ): SupervisorOverride {
        if (mb_strlen(trim($reasonText)) < 15) {
            throw new RuntimeException('Override reason is insufficient.');
        }

        $attestation = SupervisorOverrideAuthService::consume($attestationToken);

        if (
            $attestation['target_type'] !== $targetType ||
            $attestation['target_id'] !== $targetId ||
            $attestation['device_fingerprint'] !== $deviceFingerprint
        ) {
            throw new RuntimeException('Override attestation context mismatch.');
        }

        return DB::transaction(function () use (
            $attestation,
            $eventType,
            $targetType,
            $targetId,
            $reasonCode,
            $reasonText,
            $authFactors,
            $deviceFingerprint
        ) {
            $previous = SupervisorOverride::query()
                ->orderByDesc('created_at')
                ->first();

            $payload = [
                'supervisor_id'     => $attestation['supervisor_id'],
                'event_type'        => $eventType,
                'target_type'       => $targetType,
                'target_id'         => $targetId,
                'reason_code'       => $reasonCode,
                'reason_text'       => $reasonText,
                'auth_factors'      => $authFactors,
                'device_fingerprint'=> $deviceFingerprint,
                'prev_hash'         => $previous?->record_hash,
            ];

            $payloadHash = hash('sha256', json_encode($payload));
            $recordHash  = hash('sha256', ($previous?->record_hash ?? '') . $payloadHash);

            return SupervisorOverride::create([
                'id'               => Str::uuid()->toString(),
                'supervisor_id'    => $payload['supervisor_id'],
                'event_type'       => $eventType,
                'target_type'      => $targetType,
                'target_id'        => $targetId,
                'reason_code'      => $reasonCode,
                'reason_text'      => $reasonText,
                'auth_factors'     => $authFactors,
                'device_fingerprint'=> $deviceFingerprint,
                'payload_hash'     => $payloadHash,
                'prev_hash'        => $previous?->record_hash,
                'record_hash'      => $recordHash,
                'created_at'       => now(),
            ]);
        });
    }
}
