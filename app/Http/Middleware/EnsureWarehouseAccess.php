<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnsureWarehouseAccess
{
    public function handle(Request $request, Closure $next)
    {
        // Skip warehouse access check for QR fulfillment scan
    if ($request->is('api/fulfillments/scan')) {
        return $next($request);
    }
        $user = $request->user();

        if (!$user || !$user->business_location_id) {
            abort(403, 'Business location not assigned');
        }

        $warehouseId =
            $request->input('warehouse_id')
            ?? $request->route('warehouse')
            ?? $request->route('warehouse_id');

        if (!$warehouseId) {
            abort(400, 'Warehouse not specified');
        }

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
