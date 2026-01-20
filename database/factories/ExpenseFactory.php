<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
{
    return [
        'business_id' => \App\Models\Business::factory(),
        'expense_category_id' => \App\Models\ExpenseCategory::factory(),
        'cash_register_id' => \App\Models\CashRegister::factory(),
        'business_location_id' => \App\Models\BusinessLocation::factory(),
        'amount' => $this->faker->randomFloat(2, 100, 5000),
        'operation_date' => now()->toDateString(),
        'ref_no' => 'EXP-' . strtoupper(bin2hex(random_bytes(4))),
        'status' => 'approved',
    ];
}
}
