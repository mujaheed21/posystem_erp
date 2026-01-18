<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
{
    // We fetch all existing businesses to ensure they have the core accounts
    $businesses = \App\Models\Business::all();

    foreach ($businesses as $business) {
        $defaultAccounts = [
            // ASSETS (1000-1999)
            ['name' => 'Cash at Hand', 'code' => '1001', 'type' => 'asset'],
            ['name' => 'Inventory Asset', 'code' => '1002', 'type' => 'asset'],
            ['name' => 'Accounts Receivable', 'code' => '1003', 'type' => 'asset'],
            
            // LIABILITIES (2000-2999)
            ['name' => 'Accounts Payable', 'code' => '2001', 'type' => 'liability'],
            
            // EQUITY (3000-3999)
            ['name' => 'Owner Equity', 'code' => '3001', 'type' => 'equity'],
            
            // REVENUE (4000-4999)
            ['name' => 'Sales Revenue', 'code' => '4001', 'type' => 'revenue'],
            
            // EXPENSES (5000-5999)
            ['name' => 'Cost of Goods Sold (COGS)', 'code' => '5001', 'type' => 'expense'],
            ['name' => 'Operating Expenses', 'code' => '5002', 'type' => 'expense'],
        ];

        foreach ($defaultAccounts as $acc) {
            \App\Models\Account::firstOrCreate(
                [
                    'business_id' => $business->id, 
                    'code' => $acc['code']
                ],
                [
                    'name' => $acc['name'],
                    'type' => $acc['type'],
                    'is_system_account' => true
                ]
            );
        }
    }
}
}
