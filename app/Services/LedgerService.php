<?php

namespace App\Services;

use App\Models\Account;
use App\Models\LedgerEntry;
use Illuminate\Support\Facades\DB;

class LedgerService
{
    /**
     * Post a balanced double-entry transaction.
     * Ensures the fundamental accounting equation: Assets = Liabilities + Equity
     */
    public function post(int $businessId, array $entries, $source = null, string $description = null)
    {
        return DB::transaction(function () use ($businessId, $entries, $source, $description) {
            $totalDebit = collect($entries)->sum('debit');
            $totalCredit = collect($entries)->sum('credit');

            // Verification: Debits must equal Credits
            // We use a small epsilon for float comparison safety
            if (abs($totalDebit - $totalCredit) > 0.0001) {
                throw new \Exception("Accounting Imbalance: Debits ($totalDebit) != Credits ($totalCredit)");
            }

            foreach ($entries as $entry) {
                // Find account by code - This is why setupBusinessLedger must be accurate
                $account = Account::where('business_id', $businessId)
                    ->where('code', $entry['account_code'])
                    ->firstOrFail();

                LedgerEntry::create([
                    'business_id' => $businessId,
                    'account_id'  => $account->id,
                    'debit'       => $entry['debit'] ?? 0,
                    'credit'      => $entry['credit'] ?? 0,
                    'source_type' => $source ? get_class($source) : null,
                    'source_id'   => $source ? $source->id : null,
                    'description' => $description,
                    'user_id'     => auth()->id() ?? 1, 
                    // We map the transaction date to posted_at since that's your schema's time column
                    'posted_at'   => $entry['date'] ?? now(),
                ]);
            }
        });
    }

    /**
     * Helper to find an account code by its seeded name.
     * Used by SaleService to find 'Sales', 'Inventory', etc.
     */
    public function getCode(int $businessId, string $name): string
    {
        $account = Account::where('business_id', $businessId)
            ->where('name', $name)
            ->first();

        if (!$account) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
                "System Account '{$name}' not found for Business ID: {$businessId}. Please ensure the Ledger is seeded."
            );
        }

        return $account->code;
    }
}