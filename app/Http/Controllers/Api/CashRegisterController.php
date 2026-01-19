<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CashRegisterService;
use Illuminate\Http\Request;

class CashRegisterController extends Controller
{
    protected $service;

    public function __construct(CashRegisterService $service)
    {
        $this->service = $service;
    }

    /**
     * POST /api/v1/cash-registers/{id}/close
     * Closes the shift and returns the variance report.
     */
    public function close(Request $request, $id)
    {
        $request->validate([
            'closing_amount' => 'required|numeric|min:0',
            'closing_note'   => 'nullable|string|max:255'
        ]);

        try {
            $result = $this->service->close(
                (int) $id, 
                (float) $request->closing_amount, 
                $request->closing_note
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Register closed successfully.',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }
}