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
            'business_id' => Business::factory(),

            'name' => 'Main Warehouse',
            'code' => 'MAIN-WH',
            'address' => 'Test Warehouse Address',

            // schema default applies
            'active' => true,
        ];
    }
}
