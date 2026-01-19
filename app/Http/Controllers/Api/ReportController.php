<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    protected $reportService;

    /**
     * Injecting the ReportService to handle the ledger-based analytics.
     */
    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Get the Profit & Loss Statement via API
     * * This endpoint computes Revenue, COGS, and Net Profit directly from the Ledger.
     * GET /api/v1/reports/profit-loss?start_date=2026-01-01&end_date=2026-01-31&location_id=1
     */
    public function getProfitLoss(Request $request)
    {
        $request->validate([
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'location_id' => 'nullable|integer'
        ]);

        $businessId = Auth::user()->business_id;

        $report = $this->reportService->getProfitAndLoss(
            $businessId,
            $request->start_date,
            $request->end_date,
            $request->location_id
        );

        return response()->json([
            'status' => 'success',
            'data'   => $report
        ]);
    }

    /**
     * Get Top Selling Products with Margin Analysis
     * * Provides enterprise-level insight into which products generate the most profit.
     * GET /api/v1/reports/top-products?start_date=2026-01-01&end_date=2026-01-31
     */
    public function getTopProducts(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'limit'      => 'nullable|integer|max:50',
            'location_id' => 'nullable|integer'
        ]);

        $report = $this->reportService->getTopProducts(
            Auth::user()->business_id,
            $request->start_date,
            $request->end_date,
            $request->location_id,
            $request->get('limit', 10)
        );

        return response()->json([
            'status' => 'success',
            'data'   => $report
        ]);
    }
}