<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\FulfillmentController;
use App\Http\Controllers\Api\OfflineFulfillmentController;
use App\Http\Controllers\Api\OfflineReconciliationController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\CashRegisterController;
use App\Http\Controllers\Api\StockTransferController;
use App\Http\Controllers\Api\StockAdjustmentController;
use App\Http\Controllers\Api\ValuationController;
use App\Http\Controllers\Api\StockAlertController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\WarehousePurchaseReceiptController;
use App\Http\Controllers\Api\V1\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

/**
 * Public Authentication Routes
 * These remain outside of Sanctum to allow the initial login.
 */
Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

/**
 * Protected Routes
 * Added 'web' middleware alongside 'auth:sanctum'. 
 * This is critical for session-based cookie persistence in SPAs.
 */
Route::middleware(['auth:sanctum', 'web'])->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    /**
     * Unified v1 Resource Group
     */
    Route::prefix('v1')->group(function () {
        
        // --- Authentication (Protected) ---
        Route::post('/logout', [AuthController::class, 'logout']);

        // --- Sales & Transactions ---
        Route::post('/sales', [SaleController::class, 'store'])
            ->middleware([
                'warehouse.access',
                'permission:sale.create',
                'throttle:5,1',
            ]);

        // --- Reports & Analytics ---
        Route::prefix('reports')->group(function () {
            Route::get('/profit-loss', [ReportController::class, 'getProfitLoss']);
            Route::get('/top-products', [ReportController::class, 'getTopProducts']);
        });

        // --- Cash Registers ---
        Route::prefix('cash-registers')->group(function () {
            Route::post('/{id}/close', [CashRegisterController::class, 'close']);
        });

        // --- Stock Transfers & Logistics ---
        Route::prefix('stock-transfers')->group(function () {
            Route::post('/dispatch', [StockTransferController::class, 'dispatch']);
            Route::post('/fulfill', [StockTransferController::class, 'fulfill']);
            Route::post('/{id}/receive', [StockTransferController::class, 'receive']);
            Route::get('/{id}/qrcode', [StockTransferController::class, 'generateQr']);
        });

        // --- Fulfillment (QR & Offline) ---
        Route::post('/fulfillments/scan', [FulfillmentController::class, 'scan'])
            ->middleware([
                'permission:warehouse.fulfill',
                'warehouse.access',
                'throttle:5,1',
            ]);

        Route::middleware(['permission:offline.fulfillment.approve'])
            ->prefix('offline-fulfillments')
            ->group(function () {
                Route::post('{pending}/approve', [OfflineReconciliationController::class, 'approve']);
                Route::post('{pending}/reject', [OfflineReconciliationController::class, 'reject']);
                Route::post('{pending}/reconcile', [OfflineReconciliationController::class, 'reconcile']);
            });

        // --- Purchase Management ---
        Route::post('/purchases/{purchase}/receive', [WarehousePurchaseReceiptController::class, 'store'])
            ->middleware(['permission:purchase.receive']);

        // --- Inventory Control ---
        Route::prefix('inventory')->group(function () {
            Route::post('/adjust', [StockAdjustmentController::class, 'store']);
            Route::get('/valuation', [ValuationController::class, 'index']);
            Route::get('/alerts', [StockAlertController::class, 'index']);
        });

        // --- Supplier Finance ---
        Route::prefix('suppliers')->group(function () {
            Route::post('/pay', [SupplierController::class, 'pay']);
        });
    });
});

/**
 * Public/Unauthenticated API Endpoints
 */
Route::post('/v1/fulfillments/offline-scan', [OfflineFulfillmentController::class, 'scan'])
    ->middleware(['throttle:5,1']);