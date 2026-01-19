<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SupplierService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupplierController extends Controller
{
    protected $service;

    public function __construct(SupplierService $service)
    {
        $this->service = $service;
    }

    public function pay(Request $request)
    {
        $validated = $request->validate([
            'party_id' => 'required|exists:parties,id',
            'purchase_id' => 'nullable|exists:purchases,id',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string'
        ]);

        $validated['business_id'] = Auth::user()->business_id;

        $paymentId = $this->service->recordPayment($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Payment recorded and purchase status updated.',
            'payment_id' => $paymentId
        ]);
    }
}