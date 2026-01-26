<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Get Profit and Loss statement data.
     */
    public function getProfitAndLoss(int $businessId, string $startDate, string $endDate)
    {
        // 1. Fetch Net Revenue
        $revenue = $this->getAccountBalance($businessId, 'Sales Revenue', $startDate, $endDate, 'credit');

        // 2. Fetch Cost of Goods Sold
        $cogs = $this->getAccountBalance($businessId, 'Cost of Goods Sold (COGS)', $startDate, $endDate, 'debit');

        // 3. Fetch Operating Expenses (Excluding COGS)
        $totalExpenses = $this->getCategoryBalance($businessId, 'expense', $startDate, $endDate);

        // 4. Perform Computations
        $grossProfit = $revenue - $cogs;
        $netProfit = $grossProfit - $totalExpenses;
        $margin = $revenue > 0 ? ($netProfit / $revenue) * 100 : 0;

        return [
            'data' => [
                'total_sales'    => (float) $revenue,     // Aligned with React stats.sales
                'cogs'           => (float) $cogs,
                'gross_profit'   => (float) $grossProfit,
                'total_expenses' => (float) $totalExpenses,
                'net_profit'     => (float) $netProfit,   // Aligned with React stats.profit
                'profit_margin'  => round($margin, 2) . '%'
            ]
        ];
    }

    /**
     * Calculate balance for a specific account name.
     */
    protected function getAccountBalance($businessId, $accountName, $start, $end, $side)
    {
        $totals = DB::table('ledger_entries')
            ->join('accounts', 'ledger_entries.account_id', '=', 'accounts.id')
            ->where('accounts.business_id', $businessId)
            ->where('accounts.name', $accountName)
            // Changed from posted_at to created_at to match your database records
            ->whereBetween('ledger_entries.created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->first();

        $debit = $totals->total_debit ?? 0;
        $credit = $totals->total_credit ?? 0;

        // Revenue/Credit accounts: Credit - Debit. Expense/Debit accounts: Debit - Credit.
        return ($side === 'credit') ? ($credit - $debit) : ($debit - $credit);
    }

    /**
     * Calculate aggregate balance for an account category (e.g., all expenses).
     */
    protected function getCategoryBalance($businessId, $type, $start, $end)
    {
        $totals = DB::table('ledger_entries')
            ->join('accounts', 'ledger_entries.account_id', '=', 'accounts.id')
            ->where('accounts.business_id', $businessId)
            ->where('accounts.type', $type)
            ->where('accounts.name', '!=', 'Cost of Goods Sold (COGS)') 
            ->whereBetween('ledger_entries.created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->first();

        return ($totals->total_debit ?? 0) - ($totals->total_credit ?? 0);
    }
}