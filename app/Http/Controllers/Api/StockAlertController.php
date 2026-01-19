<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StockAlertService;
use Illuminate\Support\Facades\Auth;

class StockAlertController extends Controller
{
    protected $service;

    public function __construct(StockAlertService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /api/v1/inventory/alerts
     */
    public function index()
    {
        $alerts = $this->service->getLowStockAlerts(Auth::user()->business_id);

        return response()->json([
            'status' => 'success',
            'count' => $alerts->count(),
            'data' => $alerts
        ]);
    }
}