<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\SaleService; // Import stays the same
use Illuminate\Validation\ValidationException;
use Exception;

class SaleController extends Controller
{
    protected $saleService;

    // 1. We inject the service here. Laravel automatically handles the "new up" process.
    public function __construct(SaleService $saleService)
    {
        $this->saleService = $saleService;
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'integer'],
            'subtotal'     => ['required', 'numeric'],
            'discount'     => ['nullable', 'numeric'],
            'tax'          => ['nullable', 'numeric'],
            'total'        => ['required', 'numeric'],
            'items'        => ['required', 'array', 'min:1'],
            'items.*.product_id'  => ['required', 'integer'],
            'items.*.quantity'    => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price'  => ['required', 'numeric'],
            'items.*.total_price' => ['required', 'numeric'],
        ]);

        try {
            // 2. We call the service via $this->saleService instead of statically.
            // Note: We pass $data and $data['items'] separately to match your Service's signature.
            $saleId = $this->saleService->create($data, $data['items']);

            return response()->json([
                'status'  => 'success',
                'sale_id' => $saleId,
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}