<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1\Account as AccountControllers;

Route::name('auth.')->group(function() {
    Route::post('/login', [AccountControllers\AuthController::class, 'login'])
        ->name('login');
});

Route::middleware('auth:sanctum')->group(function() {
    Route::name('auth.')->group(function() {
        Route::post('/logout', [AccountControllers\AuthController::class, 'logout'])
            ->name('logout');
        Route::get('/me', [AccountControllers\AuthController::class, 'me'])
            ->name('me');
    });

    Route::name('users.')->prefix('/accounts/users')->group(function () {
        Route::get('/', [AccountControllers\UserController::class, 'index'])
            ->middleware('ability:super:*,account-user:*,account-user:view')
            ->name('index');
        Route::post('/', [AccountControllers\UserController::class, 'store'])
            ->middleware('ability:super:*,account-user:*,account-user:create')
            ->name('store');
        Route::get('/{user}', [AccountControllers\UserController::class, 'show'])
            ->middleware('ability:super:*,account-user:*,account-user:view')
            ->name('show');
        Route::put('/{user}', [AccountControllers\UserController::class, 'update'])
            ->middleware('ability:super:*,account-user:*,account-user:update')
            ->name('update');
        Route::delete('/{user}', [AccountControllers\UserController::class, 'delete'])
            ->middleware('ability:super:*,account-user:*,account-user:delete')
            ->name('delete');
    });

    Route::name('roles.')->prefix('/accounts/roles')->group(function () {
        Route::get('/', [AccountControllers\RoleController::class, 'index'])
            ->middleware('ability:super:*,account-role:*,account-role:view')
            ->name('index');
        Route::post('/', [AccountControllers\RoleController::class, 'store'])
            ->middleware('ability:super:*,account-role:*,account-role:view')
            ->name('store');
        Route::get('/{role}', [AccountControllers\RoleController::class, 'show'])
            ->middleware('ability:super:*,account-role:*,account-role:view')
            ->name('show');
        Route::put('/{role}', [AccountControllers\RoleController::class, 'update'])
            ->middleware('ability:super:*,account-role:*,account-role:view')
            ->name('update');
        Route::delete('/{role}', [AccountControllers\RoleController::class, 'delete'])
            ->middleware('ability:super:*,account-role:*,account-role:view')
            ->name('delete');
    });
});
