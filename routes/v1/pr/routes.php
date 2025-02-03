<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1 as MainControllers;

Route::middleware('auth:sanctum')->group(function() {
    Route::name('purchase_requests.')->prefix('/purchase-requests')->group(function () {
        Route::get('/', [MainControllers\PurchaseOrderController::class, 'index'])
            ->middleware('ability:super:*,head:*,pr:*,pr:view')
            ->name('index');
        Route::post('/', [MainControllers\PurchaseOrderController::class, 'store'])
            ->middleware('ability:super:*,pr:*,pr:create')
            ->name('store');
        Route::get('/{purchaseRequest}', [MainControllers\PurchaseOrderController::class, 'show'])
            ->middleware('ability:super:*,pr:*,pr:view')
            ->name('show');
        Route::put('/{purchaseRequest}', [MainControllers\PurchaseOrderController::class, 'update'])
            ->middleware('ability:super:*,pr:*,pr:update')
            ->name('update');
        Route::put('/{purchaseRequest}', [MainControllers\PurchaseOrderController::class, 'submitForApproval'])
            ->name('submit');
        Route::put('/{purchaseRequest}', [MainControllers\PurchaseOrderController::class, 'approveForCashAvailability'])
            ->middleware('ability:super:*,head:*,supply:*,budget:*,pr:*,pr:approve-cash-available')
            ->name('approve_for_cash_availability');
        Route::put('/{purchaseRequest}', [MainControllers\PurchaseOrderController::class, 'approve'])
            ->middleware('ability:super:*,head:*,supply:*,pr:*,pr:approve')
            ->name('approve');
        Route::put('/{purchaseRequest}', [MainControllers\PurchaseOrderController::class, 'disapprove'])
            ->middleware('ability:super:*,head:*,supply:*,pr:*,pr:disapprove')
            ->name('update');
        Route::put('/{purchaseRequest}', [MainControllers\PurchaseOrderController::class, 'cancel'])
            ->name('cancel');
    });
});
