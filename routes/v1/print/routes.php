<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1 as MainControllers;

Route::middleware('auth:sanctum')->group(function() {
    Route::name('documents_prints.')->prefix('/documents')->group(function () {
        Route::get('/{document}/prints/{documentId}', [MainControllers\PrintController::class, 'index'])
            ->name('index');
    });
});
