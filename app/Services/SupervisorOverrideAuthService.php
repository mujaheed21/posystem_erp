<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class SupervisorOverrideAuthService
{
    /**
     * Verify supervisor and issue a single-use override attestation token.
     */
    public static function authenticate(
        User $supervisor,
        string $pin,
        string $deviceFingerprint,
        string $targetType,
        string $targetId
    ): string {
        if (! $supervisor->hasRole('Supervisor')) {
            throw new RuntimeException('User is not authorized as supervisor.');
        }

        if (! Hash::check($pin, $supervisor->override_pin)) {
            throw new RuntimeException('Invalid supervisor PIN.');
        }

        $rateKey = "supervisor_override_rate:{$supervisor->id}";

        if (Cache::get($rateKey, 0) >= 5) {
            throw new RuntimeException('Too many failed override attempts.');
        }

        Cache::increment($rateKey);
        Cache::put($rateKey, Cache::get($rateKey), now()->addMinutes(10));

        $token = Str::uuid()->toString();

        Cache::put(
            "supervisor_override_token:{$token}",
            [
                'supervisor_id'      => $supervisor->id,
                'target_type'        => $targetType,
                'target_id'          => $targetId,
                'device_fingerprint' => $deviceFingerprint,
                'issued_at'          => now(),
            ],
            now()->addMinutes(5)
        );

        return $token;
    }

    /**
     * Consume and invalidate an override attestation token.
     */
    public static function consume(string $token): array
    {
        $key = "supervisor_override_token:{$token}";

        $payload = Cache::pull($key);

        if (! $payload) {
            throw new RuntimeException('Override token is invalid or expired.');
        }

        return $payload;
    }
}
