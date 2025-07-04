<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1 as MediaControllers;

Route::name('media.')->prefix('/media')->group(function () {
    Route::get('/', [MediaControllers\MediaController::class, 'show'])
        ->name('show');
        
    Route::middleware('auth:sanctum')->group(function() {
        Route::post('/', [MediaControllers\MediaController::class, 'store'])
            ->name('store');
    });
});