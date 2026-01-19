<?php

namespace App\Services;

use App\Models\CashRegister;
use Illuminate\Support\Facades\DB;

class CashRegisterService
{
    /**
     * Close the register and calculate variance.
     */
    public function close(int $registerId, float $actualAmount, string $note = null)
    {
        return DB::transaction(function () use ($registerId, $actualAmount, $note) {
            $register = CashRegister::findOrFail($registerId);

            // Using the Model's accessor: Opening + Sales - Expenses
            $expectedAmount = $register->expected_balance;
            
            // Variance: Positive means extra money, negative means missing money
            $variance = $actualAmount - $expectedAmount;

            $register->update([
                'closing_amount' => $actualAmount,
                'status'         => 'closed',
                'closed_at'      => now(),
                'closing_note'   => $note,
            ]);

            return [
                'expected' => (float) $expectedAmount,
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