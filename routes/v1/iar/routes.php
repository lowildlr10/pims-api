<?php

use App\Http\Controllers\V1 as MainControllers;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::name('inspection_acceptance_reports.')->prefix('/inspection-acceptance-reports')->group(function () {
        Route::get('/', [MainControllers\InspectionAcceptanceReportController::class, 'index'])
            ->middleware('ability:super:*,head:*,supply:*,iar:*,iar:view')
            ->name('index');
        Route::get('/{inspectionAcceptanceReport}', [MainControlalers\InspectionAcceptanceReportController::class, 'show'])
            ->middleware('ability:super:*,supply:*,iar:*,iar:view')
            ->name('show');
        Route::put('/{inspectionAcceptanceReport}', [MainControllers\InspectionAcceptanceReportController::class, 'update'])
            ->middleware('ability:super:*,supply:*,iar:*,iar:update')
            ->name('update');
        Route::put('/{inspectionAcceptanceReport}/pending', [MainControllers\InspectionAcceptanceReportController::class, 'pending'])
            ->middleware('ability:super:*,supply:*,iar:*,iar:pending')
            ->name('pending');
        Route::put('/{inspectionAcceptanceReport}/inspect', [MainControllers\InspectionAcceptanceReportController::class, 'inspect'])
            ->middleware('ability:super:*,supply:*,iar:*,iar:inspect')
            ->name('inspect');
        // Route::put('/{inspectionAcceptanceReport}/accept', [MainControllers\InspectionAcceptanceReportController::class, 'accept'])
        //     ->middleware('ability:super:*,supply:*,iar:*,iar:accept')
        //     ->name('accept');
    });
});
