<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Protected Routes (Requires a valid Sanctum Token)
Route::middleware('auth:sanctum')->group(function () {

    /**
     * User Context
     */
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    /**
     * Target 5: Profit & Loss Analytics Engine
     */
    Route::prefix('v1/reports')->group(function () {
        
        // P&L Statement: ?start_date=YYYY-MM-DD&end_date=YYYY-MM-DD
        Route::get('/profit-loss', [ReportController::class, 'getProfitLoss']);

        // Top Selling Products: ?start_date=YYYY-MM-DD&end_date=YYYY-MM-DD
        Route::get('/top-products', [ReportController::class, 'getTopProducts']);

    });

    /**
     * Future Target Endpoints (Placeholders)
     * Commented out to prevent ReflectionException until Controllers are created.
     */
    Route::prefix('v1/inventory')->group(function () {
        // Route::get('/stock-valuation', [InventoryController::class, 'getValuation']);
    });

});