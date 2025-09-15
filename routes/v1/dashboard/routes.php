<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1 as MainControllers;

Route::middleware('auth:sanctum')->group(function() {
    Route::name('dashboard.')->prefix('/dashboard')->group(function () {
        Route::get('/', [MainControllers\DashboardController::class, 'index'])
            ->name('index');
    });
});
