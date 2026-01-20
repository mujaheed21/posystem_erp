<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Account;
use App\Services\AuditService;
use App\Services\LedgerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StockExpenseService
{
    protected $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    /**
     * Record a manual Cash-Out (Petty Cash/Expense).
     */
    public function recordCashOut(array $data): Expense
    {
        return DB::transaction(function () use ($data) {
            $isAutoApproved = $data['amount'] <= 5000;
            $currentUserId = Auth::id();

            $expense = Expense::create([
                'business_id'          => $data['business_id'],
                'expense_category_id'  => $data['expense_category_id'],
                'cash_register_id'     => $data['cash_register_id'],
                'business_location_id' => $data['business_location_id'],
                'amount'               => $data['amount'],
                'operation_date'       => $data['operation_date'] ?? now()->toDateString(), 
                'ref_no'               => $data['ref_no'] ?? 'EXP-' . strtoupper(bin2hex(random_bytes(4))),
                'note'                 => $data['note'] ?? null,
                'user_id'              => $currentUserId,
                'status'               => $isAutoApproved ? 'approved' : 'pending',
                'approved_by'          => $isAutoApproved ? $currentUserId : null,
                'approved_at'          => $isAutoApproved ? now() : null,
            ]);

            // TARGET 4: Automatic Ledger Posting
            if ($expense->status === 'approved') {
                $this->postToLedger($expense);
            }

            AuditService::log('cash_out', 'finance', 'expenses', $expense->id, [
                'amount' => $expense->amount,
                'status' => $expense->status,
            ]);

            return $expense;
        });
    }

    /**
     * Manually approve a pending expense.
     */
    public function approveExpense(int $expenseId): bool
    {
        return DB::transaction(function () use ($expenseId) {
            $expense = Expense::findOrFail($expenseId);
            
            if ($expense->status === 'approved') return true;

            $updated = $expense->update([
                'status'      => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            if ($updated) {
                // TARGET 4: Automatic Ledger Posting
                $this->postToLedger($expense);
            }

            return $updated;
        });
    }

    /**
     * Internal logic for Double-Entry Posting
     */
    protected function postToLedger(Expense $expense)
    {
        // 1. Get Expense Account Code from the Category relation
        // We load it to ensure we have the ledger_account_id
        $categoryAccount = Account::find($expense->category->ledger_account_id);
        
        // 2. Identify the Cash Account Code for this Business
        // Using your LedgerService helper to find the 'Cash at Hand' code
        $cashAccountCode = $this->ledgerService->getCode($expense->business_id, 'Cash at Hand');

        if (!$categoryAccount || !$cashAccountCode) {
            return; // Or throw an exception if accounting must be strict
        }

        $entries = [
            [
                'account_code' => $categoryAccount->code, // Debit: The Expense (e.g., Fuel)
                'debit'        => $expense->amount,
                'credit'       => 0,
                'date'         => $expense->operation_date,
            ],
            [
                'account_code' => $cashAccountCode,       // Credit: The Cash Asset
                'debit'        => 0,
                'credit'       => $expense->amount,
                'date'         => $expense->operation_date,
            ]
        ];

        $this->ledgerService->post(
            $expense->business_id, 
            $entries, 
            $expense, 
            "Expense Payment: {$expense->ref_no}"
        );
    }
}