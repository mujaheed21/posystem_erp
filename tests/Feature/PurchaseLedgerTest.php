<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Business;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Party;
use App\Services\PurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PurchaseLedgerTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Set up the required Chart of Accounts for the business.
     */
    protected function seedLedgerAccounts(int $businessId)
    {
        $accounts = [
            ['name' => 'Inventory Asset', 'code' => '1002', 'type' => 'asset'],
            ['name' => 'Accounts Payable', 'code' => '2001', 'type' => 'liability'],
        ];

        foreach ($accounts as $account) {
            Account::create(array_merge($account, [
                'business_id' => $businessId,
                'is_system_account' => 1
            ]));
        }
    }

    /** @test */
    public function a_purchase_updates_stock_and_posts_to_ledger()
    {
        // 1. Setup the basic business environment
        $business = Business::factory()->create();
        $this->seedLedgerAccounts($business->id);

        $user = User::factory()->create([
            'business_id' => $business->id
        ]);

        $warehouse = Warehouse::factory()->create([
            'business_id' => $business->id
        ]);

        $product = Product::factory()->create([
            'business_id' => $business->id,
            'cost_price'  => 0 // Starting cost
        ]);

        // 2. Create the Supplier (The Party) - This prevents the FK error
        $supplier = Party::create([
            'business_id' => $business->id,
            'name'        => 'Global Suppliers Ltd',
            'type'        => 'supplier',
            'active'      => 1
        ]);

        $this->actingAs($user);

        // 3. Prepare the Purchase Data (₦5,000 total)
        $data = [
            'warehouse_id' => $warehouse->id,
            'supplier_id'  => $supplier->id,
            'subtotal'     => 5000,
            'tax'          => 0,
            'total'        => 5000,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity'   => 10,
                    'unit_cost'  => 500,
                    'total_cost' => 5000
                ]
            ]
        ];

        // 4. Execute the Purchase
        PurchaseService::create($data);

        // --- ASSERTIONS ---

        // 5. Verify the Purchase Record exists
        $this->assertDatabaseHas('purchases', [
            'business_id' => $business->id,
            'supplier_id' => $supplier->id,
            'total'       => 5000
        ]);

        // 6. Verify Physical Stock increased
        $this->assertDatabaseHas('warehouse_stock', [
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'quantity'     => 10
        ]);

        // 7. Verify the Product's last cost was updated
        $this->assertEquals(500, $product->fresh()->cost_price);

        // 8. Verify the Financial Ledger Entries
        // Check for the Debit (Inventory Asset increased)
        $this->assertDatabaseHas('ledger_entries', [
            'business_id' => $business->id,
            'debit'       => 5000,
            'credit'      => 0
        ]);

        // Check for the Credit (Accounts Payable liability increased)
        $this->assertDatabaseHas('ledger_entries', [
            'business_id' => $business->id,
            'debit'       => 0,
            'credit'      => 5000
        ]);

        // 9. Verify Trial Balance Integrity (Debits MUST equal Credits)
        $totalDebits = DB::table('ledger_entries')->where('business_id', $business->id)->sum('debit');
        $totalCredits = DB::table('ledger_entries')->where('business_id', $business->id)->sum('credit');

        $this->assertEquals($totalDebits, $totalCredits, 'The Ledger is out of balance!');
        $this->assertEquals(5000, $totalDebits);
    }
}