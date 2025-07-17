<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1 as NotificationControllers;

Route::middleware('auth:sanctum')->group(function() {
    Route::name('notifications.')->prefix('/notifications')->group(function () {
        Route::get('/', [NotificationControllers\NotificationController::class, 'index'])
            ->name('notifications.show');
    });

    // Route::name('notifications.')->prefix('/notifications')->group(function () {
    //     Route::get('/', [NotificationControllers\LogController::class, 'read'])
    //         ->name('notifications.read');
    // });
});
