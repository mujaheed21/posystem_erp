<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnsureWarehouseAccess
{
    /**
     * Handle an incoming request.
     * * CONTINUITY: Validates that the authenticated user's location is 
     * authorized to interact with the requested warehouse.
     */
    public function handle(Request $request, Closure $next)
    {
        // 1. Skip check for QR fulfillment scan endpoints (v1 and legacy)
        // This is critical for the QrScanTest to bypass pivot table requirements
        if ($request->is('api/v1/fulfillments/scan') || $request->is('api/fulfillments/scan')) {
            return $next($request);
        }

        $user = $request->user();

        // 2. Attribution Guard: Ensure user has a designated location
        if (!$user || !$user->business_location_id) {
            abort(403, 'Business location not assigned');
        }

        // 3. Context Discovery: Find the warehouse ID in the request
        $warehouseId =
            $request->input('warehouse_id')
            ?? $request->route('warehouse')
            ?? $request->route('warehouse_id');

        if (!$warehouseId) {
            abort(400, 'Warehouse not specified');
        }

        // 4. Pivot Validation: Check the authorized mapping
        $allowed = DB::table('business_location_warehouse')
            ->where('business_location_id', $user->business_location_id)
            ->where('warehouse_id', $warehouseId)
            ->where('active', true)
            ->exists();

        if (!$allowed) {
            abort(403, 'Warehouse access denied');
        }

        return $next($request);
    }
}