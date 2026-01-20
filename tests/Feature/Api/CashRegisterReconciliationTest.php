<?php

namespace Tests\Feature\Api;

use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\CashRegister;
use App\Models\User;
use App\Models\Sale;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashRegisterReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reconciles_perfectly_balanced_register()
    {
        $business = Business::factory()->create();
        $user = User::factory()->create(['business_id' => $business->id]);
        $location = BusinessLocation::factory()->create(['business_id' => $business->id]);
        $warehouse = Warehouse::factory()->create(['business_id' => $business->id]);

        $register = CashRegister::create([
            'business_id' => $business->id,
            'business_location_id' => $location->id,
            'user_id' => $user->id,
            'opening_amount' => 1000.00,
            'status' => 'open',
        ]);

        // Create actual records so the Service calculation matches
        Sale::factory()->create([
            'cash_register_id' => $register->id,
            'business_id' => $business->id,
            'total' => 500.00,
            'status' => 'completed'
        ]);

        $category = ExpenseCategory::factory()->create(['business_id' => $business->id]);
        Expense::factory()->create([
            'cash_register_id' => $register->id,
            'business_id' => $business->id,
            'expense_category_id' => $category->id,
            'amount' => 100.00,
            'status' => 'approved'
        ]);

        $this->actingAs($user);

        // Expected: 1000 (Open) + 500 (Sale) - 100 (Expense) = 1400
        $response = $this->postJson("/api/v1/cash-registers/{$register->id}/close", [
            'closing_amount' => 1400.00,
            'closing_note' => 'Shift ended on time.'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.variance', 0)
            ->assertJsonPath('data.status', 'balanced');

        $this->assertEquals('closed', $register->fresh()->status);
    }

    public function test_it_detects_a_cash_shortage()
    {
        $business = Business::factory()->create();
        $user = User::factory()->create(['business_id' => $business->id]);
        $location = BusinessLocation::factory()->create(['business_id' => $business->id]);
        $warehouse = Warehouse::factory()->create(['business_id' => $business->id]);

        $register = CashRegister::create([
            'business_id' => $business->id,
            'business_location_id' => $location->id,
            'user_id' => $user->id,
            'opening_amount' => 1000.00,
            'status' => 'open',
        ]);

        // Create actual records
        Sale::factory()->create([
            'cash_register_id' => $register->id,
            'business_id' => $business->id,
            'total' => 500.00,
            'status' => 'completed'
        ]);

        $category = ExpenseCategory::factory()->create(['business_id' => $business->id]);
        Expense::factory()->create([
            'cash_register_id' => $register->id,
            'business_id' => $business->id,
            'expense_category_id' => $category->id,
            'amount' => 100.00,
            'status' => 'approved'
        ]);

        $this->actingAs($user);

        // Expected: 1400. Actual: 1350. Variance: -50
        $response = $this->postJson("/api/v1/cash-registers/{$register->id}/close", [
            'closing_amount' => 1350.00,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJson([
                'data' => [
                    'actual'   => 1350,
                    'variance' => -50,
                    'status'   => 'shortage'
                ]
            ]);
    }
}