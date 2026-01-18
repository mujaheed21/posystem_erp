<?php

namespace App\Services;

use App\Models\Account;
use App\Models\LedgerEntry;
use Illuminate\Support\Facades\DB;

class LedgerService
{
    /**
     * Post a balanced double-entry transaction.
     */
    public function post(int $businessId, array $entries, $source = null, string $description = null)
    {
        return DB::transaction(function () use ($businessId, $entries, $source, $description) {
            $totalDebit = collect($entries)->sum('debit');
            $totalCredit = collect($entries)->sum('credit');

            // Verification: Debits must equal Credits
            if (abs($totalDebit - $totalCredit) > 0.0001) {
                throw new \Exception("Accounting Imbalance: Debits ($totalDebit) != Credits ($totalCredit)");
            }

            foreach ($entries as $entry) {
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
                    'posted_at'   => now(),
                ]);
            }
        });
    }

    /**
     * Helper to find an account code by its seeded name.
     */
    public function getCode(int $businessId, string $name): string
    {
        return Account::where('business_id', $businessId)
            ->where('name', $name)
            ->firstOrFail()->code;
    }
}