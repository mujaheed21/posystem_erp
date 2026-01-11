<?php

namespace Database\Factories;

use App\Models\OfflineFulfillmentPending;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Sale;
use App\Models\Warehouse;

class OfflineFulfillmentPendingFactory extends Factory
{
    protected $model = OfflineFulfillmentPending::class;

    public function definition(): array
    {
       return [
    // REQUIRED FKs (schema enforced)
    'sale_id' => Sale::factory(),
    'warehouse_id' => Warehouse::factory(),

    'state' => 'pending',

    // Supervisor-related fields
    'approved_by' => null,
    'approved_at' => null,
    'rejected_reason' => null,

    // Fulfillment timing
    'fulfilled_at' => null,

    // Override control
    'requires_override' => false,

    // Minimal valid payload
    'payload' => [
        [
            'product_id' => 1,
            'quantity'   => 1,
        ],
    ],
];


    }

    public function approved(): self
    {
        return $this->state(fn () => [
            'state' => 'approved',
        ]);
    }

    public function requiresOverride(): self
    {
        return $this->state(fn () => [
            'requires_override' => true,
        ]);
    }
}
