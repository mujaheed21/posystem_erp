<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OfflineQrVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        if ((int)$payload['warehouse_id'] !== (int)$request->header('X-Warehouse-ID')) {
            return response()->json([
                'status' => 'rejected',
                'reason' => 'warehouse_mismatch',
            ], 403);
        }

        DB::table('offline_fulfillment_pendings')->insert([
            'sale_id'       => $payload['sale_id'],
            'warehouse_id'  => $payload['warehouse_id'],
            'items_hash'    => $payload['items_hash'],
            'payload'       => json_encode($payload),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return response()->json([
            'status' => 'approved_offline',
            'message' => 'Supervisor approval required. Release goods.',
        ]);
    }
}
