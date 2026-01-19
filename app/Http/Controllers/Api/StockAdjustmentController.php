<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StockAdjustmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockAdjustmentController extends Controller
{
    protected $service;

    public function __construct(StockAdjustmentService $service)
    {
        $this->service = $service;
    }

    /**
     * POST /api/v1/inventory/adjust
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id'      => 'required|exists:warehouses,id',
            'type'              => 'required|in:damage,leakage,expired,correction,theft',
            'notes'             => 'nullable|string',
            'items'             => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|numeric|min:0.001',
        ]);

        $validated['business_id'] = Auth::user()->business_id;
        $validated['user_id'] = Auth::id();

        try {
            $adjustment = $this->service->adjust($validated);
            return response()->json([
                'status' => 'success',
                'message' => 'Stock adjusted successfully.',
                'data' => $adjustment->load('items')
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }
}