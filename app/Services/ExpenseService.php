<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\CashRegister;
use App\Models\ExpenseCategory;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    /**
     * Records an expense and handles all downstream financial impacts.
     */
    public function record(array $data)
    {
        return DB::transaction(function () use ($data) {
            // 1. Validate the active register
            $register = CashRegister::where('user_id', auth()->id())
                ->where('status', 'open')
                ->first();

            if (!$register) {
                throw new \Exception("No active cash register found. Please open a register first.");
            }

            // 2. Retrieve Category for Ledger Mapping
            $category = ExpenseCategory::findOrFail($data['expense_category_id']);

            // 3. Create the Expense Record
            $expense = Expense::create(array_merge($data, [
                'business_id' => auth()->user()->business_id,
                'cash_register_id' => $register->id,
            ]));

            // 4. Update the Register's running total
            $register->increment('total_cash_expenses', $data['amount']);

            // 5. AUTOMATIC LEDGER POSTING
            $this->postToLedger($expense, $category->ledger_account_id);

            return $expense;
        });
    }

    protected function postToLedger($expense, $expenseAccountId)
{
    $cashAccount = DB::table('accounts')
        ->where('business_id', $expense->business_id)
        ->where('code', '1001')
        ->first();

    if (!$cashAccount) {
        throw new \Exception("Cash account not found.");
    }

    $entryData = [
        'business_id' => $expense->business_id,
        'user_id' => auth()->id() ?? 1,
        'source_type' => 'expense',
        'source_id' => $expense->id,
        'description' => $expense->note ?? 'Expense Entry',
        'posted_at' => $expense->operation_date, // Use posted_at instead of transaction_date
        'created_at' => now(),
        'updated_at' => now(),
    ];

    // DEBIT Expense
    DB::table('ledger_entries')->insert([
        'business_id' => $expense->business_id,
        'account_id'  => $expenseAccountId,
        'debit'       => $expense->amount,
        'credit'      => 0,
        'source_type' => 'expense',
        'source_id'   => $expense->id,
        'description' => $expense->note,
        'user_id'     => auth()->id() ?? 1,
        'posted_at'   => $expense->operation_date, // Map operation_date to posted_at
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);

    // CREDIT Cash
    DB::table('ledger_entries')->insert(array_merge($entryData, [
        'account_id' => $cashAccount->id,
        'debit' => 0,
        'credit' => $expense->amount,
    ]));
}
}