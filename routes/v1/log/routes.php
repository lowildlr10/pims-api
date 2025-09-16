<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1 as LogControllers;

Route::middleware('auth:sanctum')->group(function() {
    Route::name('logs.')->prefix('/logs')->group(function () {
        Route::get('/', [LogControllers\LogController::class, 'index'])
            ->middleware('ability:super:*,system-log:*,system-log:update')
            ->name('show');
    });
});
