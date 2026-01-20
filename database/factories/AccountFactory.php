<?php

namespace Database\Factories;

use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'name' => $this->faker->word . ' Expense Account',
            'code' => $this->faker->unique()->numberBetween(5000, 5999),
            'type' => 'expense', // Changed from 'account_type' to 'type'
            'is_system_account' => 0,
        ];
    }
}