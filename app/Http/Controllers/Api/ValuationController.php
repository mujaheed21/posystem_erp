<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ValuationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ValuationController extends Controller
{
    protected $service;

    public function __construct(ValuationService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Session expired. Please log in.'], 401);
        }

        $businessId = (int) ($user->business_id ?? 0);
        if ($businessId === 0) {
            return response()->json(['message' => 'Business ID missing.'], 403);
        }

        try {
            // Fetch raw values from service
            $locationValuations = $this->service->getLocationValuation($businessId);
            $inTransitRaw = $this->service->getInTransitValuation($businessId);

            /**
             * FIX: We ensure 'transit_value' is a float and never null.
             * The test expects data.in_transit.transit_value to be 4000.0
             */
            $transitValue = isset($inTransitRaw['transit_value']) 
                ? (float) $inTransitRaw['transit_value'] 
                : 0.0;

            return response()->json([
                'status' => 'success',
                'data' => [
                    'by_location' => $locationValuations,
                    'in_transit'  => [
                        'transit_value' => $transitValue
                    ],
                    'timestamp'   => now()->toDateTimeString(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Valuation Error: ' . $e->getMessage()
            ], 500);
        }
    }
}