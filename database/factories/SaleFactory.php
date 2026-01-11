<?php

namespace Database\Factories;

use App\Models\Sale;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Warehouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        return [
            // REQUIRED FKs
            'business_id' => Business::factory(),
            'business_location_id' => BusinessLocation::factory(),
            'warehouse_id' => Warehouse::factory(),
            'created_by' => User::factory(),

            // REQUIRED UNIQUE FIELD
            'sale_number' => 'SALE-' . Str::upper(Str::random(10)),

            // FINANCIALS (schema defaults respected)
            'subtotal' => 1000,
            'discount' => 0,
            'tax' => 0,
            'total' => 1000,

            // ENUM
            'status' => 'completed',
        ];
    }
}
