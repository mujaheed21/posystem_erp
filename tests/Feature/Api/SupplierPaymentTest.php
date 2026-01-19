<?php

namespace Tests\Feature\Api;

use App\Models\Business;
use App\Models\Warehouse;
use App\Models\User;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_payment_updates_purchase_status()
    {
        // 1. Setup Environment
        $business = Business::factory()->create();
        $user = User::factory()->create(['business_id' => $business->id]);
        $warehouse = Warehouse::factory()->create(['business_id' => $business->id]);
        
        // Create a Supplier (Party)
        $supplierId = DB::table('parties')->insertGetId([
            'business_id' => $business->id,
            'name' => 'Kano Bulk Flour Ltd',
            'type' => 'supplier',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Create a Purchase of 100,000 NGN
        // Using 'total' and 'subtotal' based on your schema dump
        $purchase = Purchase::create([
            'business_id' => $business->id,
            'warehouse_id' => $warehouse->id,
            'supplier_id' => $supplierId,
            'purchase_number' => 'PUR-' . time(),
            'subtotal' => 100000.00,
            'tax' => 0.00,
            'total' => 100000.00, 
            'paid_amount' => 0.00,
            'payment_status' => 'unpaid',
            'status' => 'received',
            'created_by' => $user->id
        ]);

        $this->actingAs($user);

        // 3. Make a Partial Payment (40,000 NGN)
        $response = $this->postJson('/api/v1/suppliers/pay', [
            'party_id' => $supplierId,
            'purchase_id' => $purchase->id,
            'amount' => 40000.00,
            'payment_method' => 'bank_transfer'
        ]);

        $response->assertStatus(200);

        // 4. Assert status is now 'partial'
        // This will now pass because it compares 40k against the 100k in the 'total' column
        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'paid_amount' => 40000.00,
            'payment_status' => 'partial'
        ]);

        // 5. Make the Remaining Payment (60,000 NGN)
        $this->postJson('/api/v1/suppliers/pay', [
            'party_id' => $supplierId,
            'purchase_id' => $purchase->id,
            'amount' => 60000.00,
            'payment_method' => 'cash'
        ]);

        // 6. Assert status is now 'paid'
        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'paid_amount' => 100000.00,
            'payment_status' => 'paid'
        ]);
    }
}