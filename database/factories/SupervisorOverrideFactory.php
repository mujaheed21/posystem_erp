<?php

namespace Database\Factories;

use App\Models\SupervisorOverride;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SupervisorOverrideFactory extends Factory
{
    protected $model = SupervisorOverride::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),

            'supervisor_id' => User::factory(),

            'event_type' => 'offline_fulfillment_override',
            'target_type' => 'App\Models\OfflineFulfillmentPending',
            'target_id' => 1, // overridden in tests

            'reason_code' => 'TEST_OVERRIDE',
            'reason_text' => 'Supervisor override for testing purposes',

            'auth_factors' => json_encode([
    'pin'    => true,
    'device' => 'test-device',
]),

            'device_fingerprint' => 'test-device',

            'payload_hash' => hash('sha256', 'payload'),
            'prev_hash' => null,
            'record_hash' => hash('sha256', 'record'),

            'created_at' => now(),
        ];
    }
}
