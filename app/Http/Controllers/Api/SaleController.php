<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\SaleService;
use Illuminate\Validation\ValidationException;
use Exception;

class SaleController extends Controller
{
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
            $saleId = SaleService::create($data);

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
