<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\FulfillmentController;
use App\Http\Controllers\Api\OfflineFulfillmentController;
use App\Http\Controllers\Api\OfflineReconciliationController;

Route::get('/', function () {
    return response()->json(['status' => 'ok']);
});
/*
|--------------------------------------------------------------------------
| Sales
|--------------------------------------------------------------------------
*/
Route::post('/api/sales', [SaleController::class, 'store'])
    ->middleware([
        'auth:sanctum',
        'warehouse.access',
        'permission:sale.create',
        'throttle:5,1',
    ]);

/*
|--------------------------------------------------------------------------
| Warehouse Fulfillment (QR Scan)
|--------------------------------------------------------------------------
*/
Route::post('/api/fulfillments/scan', [FulfillmentController::class, 'scan'])
    ->middleware([
        'auth:sanctum',
        'permission:warehouse.fulfill',
        'warehouse.access',
        'throttle:5,1',
    ]);
/*
|---------------------------------------------------------------------------
| Warehouse Offline Fulfillment
|---------------------------------------------------------------------------
*/
    Route::post('/api/fulfillments/offline-scan', [OfflineFulfillmentController::class, 'scan'])
    ->middleware(['throttle:5,1']);

    Route::post('/purchases/{purchase}/receive', [
    \App\Http\Controllers\Api\WarehousePurchaseReceiptController::class,
    'store'
])->middleware([
    'auth:sanctum',
    'permission:purchase.receive',
]);
Route::middleware(['auth:sanctum', 'permission:offline.fulfillment.approve'])
    ->prefix('api/offline-fulfillments')
    ->group(function () {
        Route::post('{pending}/approve', [OfflineReconciliationController::class, 'approve']);
        Route::post('{pending}/reject', [OfflineReconciliationController::class, 'reject']);
        Route::post('{pending}/reconcile', [OfflineReconciliationController::class, 'reconcile']);
    });