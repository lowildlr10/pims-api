<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1 as CompanyControllers;

Route::get('/companies', [CompanyControllers\CompanyController::class, 'show'])
    ->name('companies.show');

Route::middleware('auth:sanctum')->group(function() {
    Route::name('companies.')->prefix('/companies')->group(function () {
        Route::put('/', [CompanyControllers\CompanyController::class, 'update'])
            ->middleware('ability:super:*,company:*,company:update')
            ->name('update');
    });
});
