<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1 as MainControllers;

Route::middleware('auth:sanctum')->group(function() {
    Route::name('inventories.')->prefix('/inventories')->group(function() {
        Route::name('supplies.')->prefix('/supplies')->group(function() {
            Route::get('/', [MainControllers\SupplyController::class, 'index'])
                ->middleware('ability:super:*,head:*,supply:*,inv-supply:*,inv-supply:view')
                ->name('index');
            Route::post('/', [MainControllers\SupplyController::class, 'store'])
                ->middleware('ability:super:*,supply:*,inv-supply:*,inv-supply:create')
                ->name('store');
            Route::get('/{supply}', [MainControllers\SupplyController::class, 'show'])
                ->middleware('ability:super:*,supply:*,inv-supply:*,inv-supply:view')
                ->name('show');
            Route::put('/{supply}', [MainControllers\SupplyController::class, 'update'])
                ->middleware('ability:super:*,supply:*,inv-supply:*,inv-supply:update')
                ->name('update');
        });

        Route::name('issuances.')->prefix('/issuances')->group(function() {
            Route::get('/', [MainControllers\InventoryIssuanceController::class, 'index'])
                ->middleware('ability:super:*,head:*,supply:*,inv-issuance:*,inv-issuance:view')
                ->name('index');
            Route::post('/', [MainControllers\InventoryIssuanceController::class, 'store'])
                ->middleware('ability:super:*,supply:*,inv-issuance:*,inv-issuance:create')
                ->name('store');
            Route::get('/{issuance}', [MainControllers\InventoryIssuanceController::class, 'show'])
                ->middleware('ability:super:*,supply:*,inv-issuance:*,inv-issuance:view')
                ->name('show');
            Route::put('/{issuance}', [MainControllers\InventoryIssuanceController::class, 'update'])
                ->middleware('ability:super:*,supply:*,inv-issuance:*,inv-issuance:update')
                ->name('update');
        });
    });
});

