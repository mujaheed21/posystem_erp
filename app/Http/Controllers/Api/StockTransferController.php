<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockTransfer;
use App\Services\StockTransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class StockTransferController extends Controller
{
    protected $service;

    public function __construct(StockTransferService $service)
    {
        $this->service = $service;
    }

    /**
     * POST /api/v1/stock-transfers/dispatch
     * Start the transfer process from the warehouse.
     */
    public function dispatch(Request $request)
    {
        $validated = $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id'   => 'required|exists:warehouses,id',
            'items'             => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|numeric|min:0.01',
        ]);

        $validated['business_id'] = Auth::user()->business_id;
        $validated['user_id'] = Auth::id();

        try {
            $transfer = $this->service->dispatch($validated);
            return response()->json([
                'status' => 'success',
                'message' => 'Stock dispatched and is now in transit.',
                'data' => $transfer->load('items')
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * POST /api/v1/stock-transfers/{id}/receive
     * Finalize the transfer at the market stall.
     */
    public function receive($id)
    {
        try {
            $transfer = $this->service->receive((int) $id, Auth::id());
            return response()->json([
                'status' => 'success',
                'message' => 'Stock received and added to inventory.',
                'data' => $transfer
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * GET /api/v1/stock-transfers/{id}/qrcode
     * Generates a QR code image for the physical waybill.
     */
    public function generateQr($id)
    {
        try {
            $transfer = StockTransfer::where('business_id', Auth::user()->business_id)
                ->findOrFail($id);
            
            // The QR payload contains the ID and action for the scanner app
            $qrData = json_encode([
                'action' => 'receive_transfer',
                'id' => $transfer->id,
                'tn' => $transfer->transfer_number
            ]);

            return response(QrCode::format('png')->size(300)->margin(2)->generate($qrData))
                ->header('Content-Type', 'image/png');
                
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Unable to generate QR code.'], 404);
        }
    }

    /**
 * POST /api/v1/stock-transfers/fulfill
 * Endpoint for QR scanner to finalize a transfer.
 */
public function fulfill(Request $request)
{
    $request->validate([
        'token' => 'required|string|min:32'
    ]);

    try {
        // Use the new service method
        $transfer = $this->service->fulfillByToken($request->token, Auth::id());
        
        return response()->json([
            'status' => 'success',
            'message' => 'QR Verified. Stock has been moved to destination.',
            'data' => $transfer
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 400);
    }
}
}