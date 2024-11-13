<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\Account as AccountControllers;

Route::name('user.')
    ->group(function() {
        Route::post('/login', [AccountControllers\AuthController::class, 'login'])
            ->name('login');
    });

Route::middleware('auth:sanctum')->group(function() {
    Route::name('user.')
        ->group(function() {
            Route::post('/logout', [AccountControllers\AuthController::class, 'logout'])
                ->name('logout');
            Route::get('/me', [AccountControllers\AuthController::class, 'me'])
                ->name('me');
        });
});
