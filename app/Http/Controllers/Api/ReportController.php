<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function getProfitLoss(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Session expired. Please log in.'], 401);
        }

        $request->mergeIfMissing([
            'start_date' => Carbon::now()->startOfMonth()->toDateString(),
            'end_date'   => Carbon::now()->toDateString(),
        ]);

        $validated = $request->validate([
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'location_id' => 'nullable|integer'
        ]);

        // Guard against null business_id
        $businessId = (int) ($user->business_id ?? 0);
        if ($businessId === 0) {
            return response()->json(['message' => 'No business associated with this user.'], 403);
        }

        $report = $this->reportService->getProfitAndLoss(
            $businessId,
            $validated['start_date'],
            $validated['end_date'],
            $request->location_id
        );

        return response()->json([
            'status' => 'success',
            'data'   => $report['data'],
            'metadata' => $report['metadata'] ?? null
        ]);
    }

    public function getTopProducts(Request $request)
    {
        $user = Auth::user();
        if (!$user) return response()->json(['message' => 'Unauthenticated'], 401);

        $request->mergeIfMissing([
            'start_date' => Carbon::now()->startOfMonth()->toDateString(),
            'end_date'   => Carbon::now()->toDateString(),
        ]);

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'limit'      => 'nullable|integer|max:50',
            'location_id' => 'nullable|integer'
        ]);

        $report = $this->reportService->getTopProducts(
            (int) $user->business_id,
            $validated['start_date'],
            $validated['end_date'],
            $request->location_id,
            $request->get('limit', 10)
        );

        return response()->json(['status' => 'success', 'data' => $report]);
    }
}