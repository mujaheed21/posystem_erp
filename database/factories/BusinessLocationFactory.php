<?php

namespace Database\Factories;

use App\Models\BusinessLocation;
use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;

class BusinessLocationFactory extends Factory
{
    protected $model = BusinessLocation::class;

    public function definition(): array
    {
        return [
    'business_id' => Business::factory(),

    'name' => 'Main Branch',
    'code' => 'MAIN',
    'address' => 'Test Address',

    // schema default applies
    'active' => true,
];

    }
}
