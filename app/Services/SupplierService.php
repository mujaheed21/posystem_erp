<?php

namespace App\Services;

use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

class SupplierService
{
    /**
     * Record a payment made to a supplier and update purchase statuses.
     */
    public function recordPayment(array $data)
    {
        return DB::transaction(function () use ($data) {
            // 1. Create the payment record in the ledger
            $paymentId = DB::table('supplier_payments')->insertGetId([
                'business_id' => $data['business_id'],
                'party_id'    => $data['party_id'],
                'purchase_id' => $data['purchase_id'] ?? null,
                'amount'      => $data['amount'],
                'payment_method' => $data['payment_method'],
                'paid_at'     => now(),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            // 2. If payment is linked to a specific purchase, update it
            if (isset($data['purchase_id'])) {
                $this->updatePurchasePaymentStatus($data['purchase_id'], $data['amount']);
            }

            return $paymentId;
        });
    }

    /**
     * Update the payment status of a purchase based on the 'total' column.
     */
    protected function updatePurchasePaymentStatus($purchaseId, $amount)
    {
        $purchase = Purchase::findOrFail($purchaseId);
        
        // Single Source of Truth: 'total'
        $billTotal = (float) $purchase->total;
        $newPaidAmount = (float) $purchase->paid_amount + (float) $amount;
        
        $status = 'partial';

        // Logic check: Is the bill fully covered based on 'total'?
        if ($billTotal > 0 && $newPaidAmount >= $billTotal) {
            $status = 'paid';
        } elseif ($newPaidAmount <= 0) {
            $status = 'unpaid';
        }

        $purchase->update([
            'paid_amount' => $newPaidAmount,
            'payment_status' => $status
            // Redundant total_amount removed to prevent SQL errors
        ]);
    }
}