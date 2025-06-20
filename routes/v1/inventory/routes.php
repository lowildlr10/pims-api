<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\Inventory as InventoryControllers;

Route::middleware('auth:sanctum')->group(function() {
    Route::name('inventories.')->prefix('/inventories')->group(function() {
        Route::name('supplies.')->prefix('/supplies')->group(function() {
            Route::get('/', [InventoryControllers\InventorySupplyController::class, 'index'])
                ->middleware('ability:super:*,head:*,supply:*,inv-supply:*,inv-supply:view')
                ->name('index');
            Route::post('/', [InventoryControllers\InventorySupplyController::class, 'store'])
                ->middleware('ability:super:*,supply:*,inv-supply:*,inv-supply:create')
                ->name('store');
            Route::get('/{inventorySupply}', [InventoryControllers\InventorySupplyController::class, 'show'])
                ->middleware('ability:super:*,supply:*,inv-supply:*,inv-supply:view')
                ->name('show');
            Route::put('/{inventorySupply}', [InventoryControllers\InventorySupplyController::class, 'update'])
                ->middleware('ability:super:*,supply:*,inv-supply:*,inv-supply:update')
                ->name('update');
        });

        Route::name('issuances.')->prefix('/issuances')->group(function() {
            Route::get('/', [InventoryControllers\InventoryIssuanceController::class, 'index'])
                ->middleware('ability:super:*,head:*,supply:*,inv-issuance:*,inv-issuance:view')
                ->name('index');
            Route::post('/', [InventoryControllers\InventoryIssuanceController::class, 'store'])
                ->middleware('ability:super:*,supply:*,inv-issuance:*,inv-issuance:create')
                ->name('store');
            Route::get('/{issuance}', [InventoryControllers\InventoryIssuanceController::class, 'show'])
                ->middleware('ability:super:*,supply:*,inv-issuance:*,inv-issuance:view')
                ->name('show');
            Route::put('/{issuance}', [InventoryControllers\InventoryIssuanceController::class, 'update'])
                ->middleware('ability:super:*,supply:*,inv-issuance:*,inv-issuance:update')
                ->name('update');
        });
    });
});

