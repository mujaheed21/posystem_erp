<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ValuationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ValuationController extends Controller
{
    protected $service;

    /**
     * Inject the ValuationService we just updated.
     */
    public function __construct(ValuationService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /api/v1/inventory/valuation
     * Returns the total Naira value of stock in warehouses and in-transit.
     */
    public function index()
    {
        $businessId = Auth::user()->business_id;

        try {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'by_location' => $this->service->getLocationValuation($businessId),
                    'in_transit'  => $this->service->getInTransitValuation($businessId),
                    'timestamp'   => now()->toDateTimeString(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Could not calculate valuation: ' . $e->getMessage()
            ], 500);
        }
    }
}