<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Business;
use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseCategoryFactory extends Factory
{
    protected $model = ExpenseCategory::class;

    public function definition(): array
{
    return [
        'business_id' => Business::factory(),
        'name' => $this->faker->word,
        'code' => strtoupper($this->faker->unique()->lexify('EXP-????')),
        'ledger_account_id' => Account::factory(), // This triggers the fix above
        'description' => $this->faker->sentence(),
    ];
}
}