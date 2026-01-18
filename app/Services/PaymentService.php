<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\LedgerService;
use App\Services\AuditService;

class PaymentService
{
    /**
     * Record a payment to a Supplier (Reduces Accounts Payable).
     */
    public static function paySupplier(array $data)
    {
        return DB::transaction(function () use ($data) {
            $user = Auth::user();
            $businessId = $user->business_id;

            $paymentId = DB::table('payments')->insertGetId([
                'business_id' => $businessId,
                'party_id'    => $data['supplier_id'],
                'account_id'  => $data['account_id'],
                'amount'      => $data['amount'],
                'payment_date'=> $data['payment_date'] ?? now(),
                'reference'   => $data['reference'] ?? null,
                'description' => $data['description'] ?? 'Supplier Payment',
                'created_by'  => $user->id,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            $ledgerService = app(LedgerService::class);
            $paymentMethodAccount = DB::table('accounts')->where('id', $data['account_id'])->first();

            $entries = [
                ['account_code' => $ledgerService->getCode($businessId, 'Accounts Payable'), 'debit' => $data['amount'], 'credit' => 0],
                ['account_code' => $paymentMethodAccount->code, 'debit' => 0, 'credit' => $data['amount']]
            ];

            $ledgerService->post($businessId, $entries, null, "Payment to Supplier. Ref: " . ($data['reference'] ?? "#$paymentId"), $paymentId, 'payments');

            return $paymentId;
        });
    }

    /**
     * Record a payment from a Customer (Reduces Accounts Receivable).
     */
    public static function receiveCustomerPayment(array $data)
    {
        return DB::transaction(function () use ($data) {
            $user = Auth::user();
            $businessId = $user->business_id;

            $paymentId = DB::table('payments')->insertGetId([
                'business_id' => $businessId,
                'party_id'    => $data['customer_id'],
                'account_id'  => $data['account_id'],
                'amount'      => $data['amount'],
                'payment_date'=> $data['payment_date'] ?? now(),
                'reference'   => $data['reference'] ?? null,
                'description' => $data['description'] ?? 'Customer Payment',
                'created_by'  => $user->id,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            $ledgerService = app(LedgerService::class);
            $paymentMethodAccount = DB::table('accounts')->where('id', $data['account_id'])->first();

            $entries = [
                ['account_code' => $paymentMethodAccount->code, 'debit' => $data['amount'], 'credit' => 0],
                ['account_code' => $ledgerService->getCode($businessId, 'Accounts Receivable'), 'debit' => 0, 'credit' => $data['amount']]
            ];

            $ledgerService->post($businessId, $entries, null, "Payment received from Customer. Ref: " . ($data['reference'] ?? "#$paymentId"), $paymentId, 'payments');

            return $paymentId;
        });
    }
}