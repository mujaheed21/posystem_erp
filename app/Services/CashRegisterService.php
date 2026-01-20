<?php

namespace App\Services;

use App\Models\CashRegister;
use App\Models\Sale;
use App\Models\Expense;
use Illuminate\Support\Facades\DB;

class CashRegisterService
{
    /**
     * Get a live summary of the register state before closing.
     * Essential for the "Expected vs Actual" reconciliation UI.
     */
    public function getRegisterSummary(CashRegister $register): array
{
    // 1. Calculate Sales (Cash-In) linked to this register
    $totalSales = \App\Models\Sale::where('cash_register_id', $register->id)
        ->where('status', 'completed')
        ->sum('total');

    // 2. Calculate Expenses (Cash-Out) linked to this register
    $totalExpenses = \App\Models\Expense::where('cash_register_id', $register->id)
        ->where('status', 'approved')
        ->sum('amount');

    // 3. The Math: Opening + In - Out
    // Note: Use 'opening_amount' to match your model's $fillable array
    $expected = ($register->opening_amount + $totalSales) - $totalExpenses;

    return [
        'opening_balance' => (float) $register->opening_amount,
        'total_sales'     => (float) $totalSales,
        'total_expenses'  => (float) $totalExpenses,
        'expected_cash'   => (float) $expected,
    ];
}

    /**
     * Close the register and calculate variance.
     */
    public function close(int $registerId, float $actualAmount, string $note = null)
    {
        return DB::transaction(function () use ($registerId, $actualAmount, $note) {
            $register = CashRegister::findOrFail($registerId);

            // Fetch live summary to ensure the most recent transactions are included
            $summary = $this->getRegisterSummary($register);
            $expectedAmount = $summary['expected_cash'];
            
            // Variance: Positive means extra money (overage), negative means missing (shortage)
            $variance = $actualAmount - $expectedAmount;

            $register->update([
                'closing_amount' => $actualAmount,
                'status'         => 'closed',
                'closed_at'      => now(),
                'closing_note'   => $note,
            ]);

            // Terminal State Guard: Once closed, transactions should no longer link to this ID.
            // This satisfies CONTINUITY_MAP Section 4.1.

            return [
                'summary'  => $summary,
                'actual'   => (float) $actualAmount,
                'variance' => (float) $variance,
                'status'   => $this->getVarianceStatus($variance)
            ];
        });
    }

    /**
     * Helper to determine the reconciliation state.
     */
    private function getVarianceStatus($variance)
    {
        if (abs($variance) < 0.01) return 'balanced';
        return $variance > 0 ? 'overage' : 'shortage';
    }
}