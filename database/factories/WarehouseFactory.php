<?php

namespace Database\Factories;

use App\Models\Warehouse;
use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company . ' Warehouse',
            'business_id' => Business::factory(),
        ];
    }
}
