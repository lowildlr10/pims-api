<?php

use App\Http\Controllers\V1 as MainControllers;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::name('request_quotations.')->prefix('/request-quotations')->group(function () {
        Route::get('/', [MainControllers\RequestQuotationController::class, 'index'])
            ->middleware('ability:super:*,head:*,supply:*,rfq:*,rfq:view')
            ->name('index');
        Route::post('/', [MainControllers\RequestQuotationController::class, 'store'])
            ->middleware('ability:super:*,supply:*,rfq:*,rfq:create')
            ->name('store');
        Route::get('/{requestQuotation}', [MainControllers\RequestQuotationController::class, 'show'])
            ->middleware('ability:super:*,supply:*,rfq:*,rfq:view')
            ->name('show');
        Route::put('/{requestQuotation}', [MainControllers\RequestQuotationController::class, 'update'])
            ->middleware('ability:super:*,supply:*,rfq:*,rfq:update')
            ->name('update');
        Route::put('/{requestQuotation}/issue-canvassing', [MainControllers\RequestQuotationController::class, 'issueCanvassing'])
            ->middleware('ability:super:*,supply:*,rfq:*,rfq:issue')
            ->name('issue');
        Route::put('/{requestQuotation}/canvass-complete', [MainControllers\RequestQuotationController::class, 'canvassComplete'])
            ->middleware('ability:super:*,supply:*,rfq:*,rfq:complete')
            ->name('complete');
        Route::put('/{requestQuotation}/cancel', [MainControllers\RequestQuotationController::class, 'cancel'])
            ->middleware('ability:super:*,supply:*,rfq:*,rfq:cancel')
            ->name('cancel');
    });
});
