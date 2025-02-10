<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1 as MediaControllers;

Route::middleware('auth:sanctum')->group(function() {
    Route::name('media.')->prefix('/media')->group(function () {
        Route::put('/{id}', [MediaControllers\MediaController::class, 'update'])
            ->name('update');
    });
});
