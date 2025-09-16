<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1 as MainControllers;

Route::middleware('auth:sanctum')->group(function() {
    Route::name('notifications.')->prefix('/notifications')->group(function () {
        Route::get('/', [MainControllers\NotificationController::class, 'index'])
            ->name('show');
        Route::put('/{id}/read', [MainControllers\NotificationController::class, 'markAsRead'])
            ->name('read');
        Route::put('/read/all', [MainControllers\NotificationController::class, 'markAllRead'])
            ->name('read_all');
        Route::put('/delete/all', [MainControllers\NotificationController::class, 'deleteAll'])
            ->name('delete_all');
    });
});
