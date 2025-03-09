<?php

use App\Http\Controllers\V1 as MainControllers;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::name('abstract_quotations.')->prefix('/abstract-quotations')->group(function () {
        Route::get('/', [MainControllers\AbstractQuotationController::class, 'index'])
            ->middleware('ability:super:*,head:*,supply:*,aoq:*,aoq:view')
            ->name('index');
        Route::post('/', [MainControllers\AbstractQuotationController::class, 'store'])
            ->middleware('ability:super:*,supply:*,aoq:*,aoq:create')
            ->name('store');
        Route::get('/{abstractQuotation}', [MainControllers\AbstractQuotationController::class, 'show'])
            ->middleware('ability:super:*,supply:*,aoq:*,aoq:view')
            ->name('show');
        Route::put('/{abstractQuotation}', [MainControllers\AbstractQuotationController::class, 'update'])
            ->middleware('ability:super:*,supply:*,aoq:*,aoq:update')
            ->name('update');
        Route::put('/{abstractQuotation}/pending', [MainControllers\AbstractQuotationController::class, 'pending'])
            ->middleware('ability:super:*,supply:*,aoq:*,aoq:pending')
            ->name('pending');
        Route::put('/{abstractQuotation}/approve', [MainControllers\AbstractQuotationController::class, 'approve'])
            ->middleware('ability:super:*,supply:*,aoq:*,aoq:approve')
            ->name('approve');
    });
});
