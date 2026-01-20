<?php

namespace App\Services;

use App\Models\Expense;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StockExpenseService
{
    /**
     * Record a manual Cash-Out (Petty Cash/Expense).
     * Now supports the full audit schema: user_id, status, approved_by, and approved_at.
     */
    public function recordCashOut(array $data): Expense
    {
        return DB::transaction(function () use ($data) {
            // Determine if the amount is small enough for auto-approval
            $isAutoApproved = $data['amount'] <= 5000;
            $currentUserId = Auth::id();

            // 1. Create the Expense Record
            $expense = Expense::create([
                'business_id'          => $data['business_id'],
                'expense_category_id'  => $data['expense_category_id'],
                'cash_register_id'     => $data['cash_register_id'],
                'business_location_id' => $data['business_location_id'],
                'amount'               => $data['amount'],
                // cast to date string to match DB DATE type
                'operation_date'       => $data['operation_date'] ?? now()->toDateString(), 
                'ref_no'               => $data['ref_no'] ?? 'EXP-' . strtoupper(bin2hex(random_bytes(4))),
                'note'                 => $data['note'] ?? null,
                
                // Audit & Workflow
                'user_id'              => $currentUserId,
                'status'               => $isAutoApproved ? 'approved' : 'pending',
                'approved_by'          => $isAutoApproved ? $currentUserId : null,
                'approved_at'          => $isAutoApproved ? now() : null,
            ]);

            // 2. Audit Logging
            AuditService::log(
                'cash_out',
                'finance',
                'expenses',
                $expense->id,
                [
                    'amount'      => $expense->amount,
                    'category_id' => $expense->expense_category_id,
                    'register_id' => $expense->cash_register_id,
                    'status'      => $expense->status,
                    'approved_at' => $expense->approved_at
                ]
            );

            return $expense;
        });
    }

    /**
     * Optional helper: Manually approve a pending expense later.
     */
    public function approveExpense(int $expenseId): bool
    {
        $expense = Expense::findOrFail($expenseId);
        
        return $expense->update([
            'status'      => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);
    }
}