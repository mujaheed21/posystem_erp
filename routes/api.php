<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\CashRegisterController; 
use App\Http\Controllers\Api\StockTransferController;
use App\Http\Controllers\Api\StockAdjustmentController;
use App\Http\Controllers\Api\ValuationController;
use App\Http\Controllers\Api\StockAlertController;
use App\Http\Controllers\Api\SupplierController; // Target 12 Namespace

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    /**
     * Target 5: Profit & Loss Analytics
     */
    Route::prefix('v1/reports')->group(function () {
        Route::get('/profit-loss', [ReportController::class, 'getProfitLoss']);
        Route::get('/top-products', [ReportController::class, 'getTopProducts']);
    });

    /**
     * Target 6: Cash Register & Shift Reconciliation
     */
    Route::prefix('v1/cash-registers')->group(function () {
        Route::post('/{id}/close', [CashRegisterController::class, 'close']);
    });

    /**
     * Target 7 & 8: FIFO Stock Transfers & QR Fulfillment
     */
    Route::prefix('v1/stock-transfers')->group(function () {
        Route::post('/dispatch', [StockTransferController::class, 'dispatch']);
        Route::post('/fulfill', [StockTransferController::class, 'fulfill']);
        Route::post('/{id}/receive', [StockTransferController::class, 'receive']);
        Route::get('/{id}/qrcode', [StockTransferController::class, 'generateQr']);
    });

    /**
     * Target 9, 10 & 11: Stock Adjustment, Valuation & Alerts
     */
    Route::prefix('v1/inventory')->group(function () {
        Route::post('/adjust', [StockAdjustmentController::class, 'store']);
        Route::get('/valuation', [ValuationController::class, 'index']);
        Route::get('/alerts', [StockAlertController::class, 'index']);
    });

    /**
     * Target 12: Supplier Debt & Credit Tracking
     */
    Route::prefix('v1/suppliers')->group(function () {
        Route::post('/pay', [SupplierController::class, 'pay']);
    });
});