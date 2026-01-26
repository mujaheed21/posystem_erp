<?php

namespace Tests\Helpers;

use App\Models\Business;
use App\Models\Account;

trait SeedsLedger
{
    protected function seedLedgerForBusiness(Business $business): void
    {
        $accounts = [
            ['name' => 'Cash at Hand', 'type' => 'asset', 'code' => '1001', 'is_system' => true],
            ['name' => 'Inventory Asset', 'type' => 'asset', 'code' => '1002', 'is_system' => true],
            ['name' => 'Accounts Receivable', 'type' => 'asset', 'code' => '1003', 'is_system' => true],
            ['name' => 'Accounts Payable', 'type' => 'liability', 'code' => '2001', 'is_system' => true],
            ['name' => 'Sales Revenue', 'type' => 'revenue', 'code' => '4001', 'is_system' => true],
            // Update name to match the Service's expectation
            ['name' => 'Cost of Goods Sold (COGS)', 'type' => 'expense', 'code' => '5001', 'is_system' => true],
        ];

        foreach ($accounts as $account) {
            Account::updateOrCreate(
                [
                    'business_id' => $business->id,
                    'name' => $account['name'],
                ],
                $account
            );
        }
    }
}