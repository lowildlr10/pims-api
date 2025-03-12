<?php

use App\Http\Controllers\V1 as MainControllers;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::name('purchase_orders.')->prefix('/purchase-orders')->group(function () {
        Route::get('/', [MainControllers\PurchaseOrderController::class, 'index'])
            ->middleware('ability:super:*,head:*,supply:*,po:*,po:view')
            ->name('index');
        Route::get('/{purchaseOrder}', [MainControllers\PurchaseOrderController::class, 'show'])
            ->middleware('ability:super:*,supply:*,po:*,po:view')
            ->name('show');
        Route::put('/{purchaseOrder}', [MainControllers\PurchaseOrderController::class, 'update'])
            ->middleware('ability:super:*,supply:*,po:*,po:update')
            ->name('update');
        Route::put('/{purchaseOrder}/pending', [MainControllers\PurchaseOrderController::class, 'pending'])
            ->middleware('ability:super:*,supply:*,po:*,po:pending')
            ->name('pending');
        Route::put('/{purchaseOrder}/approve', [MainControllers\PurchaseOrderController::class, 'approve'])
            ->middleware('ability:super:*,supply:*,po:*,po:approve')
            ->name('approve');
        Route::put('/{purchaseOrder}/receive', [MainControllers\PurchaseOrderController::class, 'receive'])
            ->middleware('ability:super:*,supply:*,po:*,po:receive')
            ->name('receive');
    });
});
