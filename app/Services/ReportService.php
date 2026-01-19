<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Generate P&L Data from the Ledger
     */
    public function getProfitAndLoss(int $businessId, string $startDate, string $endDate, ?int $locationId = null)
    {
        // 1. Fetch Revenue (Credits - Debits in Sales Revenue accounts)
        $revenue = $this->getAccountBalance($businessId, 'Sales Revenue', $startDate, $endDate, $locationId, 'credit');

        // 2. Fetch COGS (Debits - Credits in COGS accounts)
        $cogs = $this->getAccountBalance($businessId, 'Cost of Goods Sold (COGS)', $startDate, $endDate, $locationId, 'debit');

        // 3. Fetch Expenses (Debits - Credits in Expense accounts)
        $expenses = $this->getAccountBalance($businessId, 'Operating Expense', $startDate, $endDate, $locationId, 'debit');

        $grossProfit = $revenue - $cogs;
        $netProfit = $grossProfit - $expenses;

        return [
            'period' => ['start' => $startDate, 'end' => $endDate],
            'revenue' => $revenue,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'expenses' => $expenses,
            'net_profit' => $netProfit,
            'margin' => $revenue > 0 ? round(($netProfit / $revenue) * 100, 2) . '%' : '0%'
        ];
    }

    protected function getAccountBalance($businessId, $accountName, $start, $end, $locationId, $side)
    {
        $query = DB::table('ledger_entries')
            ->join('accounts', 'ledger_entries.account_id', '=', 'accounts.id')
            ->where('accounts.business_id', $businessId)
            ->where('accounts.name', $accountName)
            ->whereBetween('ledger_entries.created_at', [$start . ' 00:00:00', $end . ' 23:59:59']);

        if ($locationId) {
            $query->where('ledger_entries.location_id', $locationId);
        }

        $totals = $query->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')->first();

        return $side === 'credit' 
            ? ($totals->total_credit - $totals->total_debit) 
            : ($totals->total_debit - $totals->total_credit);
    }
}