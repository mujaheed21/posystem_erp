<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OfflineFulfillmentPending;
use App\Services\OfflineQrVerifier;
use Illuminate\Http\Request;

class OfflineFulfillmentController extends Controller
{
    public function scan(Request $request)
    {
        $payload = $request->all();

        if (!OfflineQrVerifier::verify($payload)) {
            return response()->json([
                'status' => 'rejected',
                'reason' => 'invalid_signature',
            ], 403);
        }

        if (now()->gt($payload['expires_at'])) {
            return response()->json([
                'status' => 'rejected',
                'reason' => 'expired',
            ], 410);
        }

        if ((int) $payload['warehouse_id'] !== (int) $request->header('X-Warehouse-ID')) {
            return response()->json([
                'status' => 'rejected',
                'reason' => 'warehouse_mismatch',
            ], 403);
        }

        OfflineFulfillmentPending::create([
            'sale_id'      => $payload['sale_id'],
            'warehouse_id' => $payload['warehouse_id'],
            'state'        => 'pending', // 🔥 CRITICAL
            'payload'      => $payload,
        ]);

        return response()->json([
            'status'  => 'pending_approval',
            'message' => 'Supervisor approval required. Release goods.',
        ]);
    }
}
