<?php

namespace Tests\Feature\Api;

use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\CashRegister;
use App\Models\User;
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

        // Opening (1000) + Sales (500) - Expenses (100) = Expected (1400)
        $register = CashRegister::create([
            'business_id' => $business->id,
            'business_location_id' => $location->id,
            'user_id' => $user->id,
            'opening_amount' => 1000.00,
            'total_cash_sales' => 500.00,
            'total_cash_expenses' => 100.00,
            'status' => 'open',
        ]);

        $this->actingAs($user);

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

        // Expected: 1400 (1000 + 500 - 100)
        $register = CashRegister::create([
            'business_id' => $business->id,
            'business_location_id' => $location->id,
            'user_id' => $user->id,
            'opening_amount' => 1000.00,
            'total_cash_sales' => 500.00,
            'total_cash_expenses' => 100.00,
            'status' => 'open',
        ]);

        $this->actingAs($user);

        // Actual count is 1350 (50 NGN missing)
        $response = $this->postJson("/api/v1/cash-registers/{$register->id}/close", [
            'closing_amount' => 1350.00,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success') // Strict path is fine for strings
            ->assertJson([ // Use assertJson for numeric data to avoid type mismatch
                'data' => [
                    'expected' => 1400.00,
                    'actual'   => 1350.00,
                    'variance' => -50.00,
                    'status'   => 'shortage'
                ]
            ]);
    }
}