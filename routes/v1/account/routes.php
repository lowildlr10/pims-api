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

    Route::name('positions.')->prefix('/accounts/positions')->group(function () {
        Route::get('/', [AccountControllers\PositionController::class, 'index'])
            // ->middleware('ability:super:*,head:*,account-position:*,account-position:view')
            ->name('index');
    });

    Route::name('designations.')->prefix('/accounts/designations')->group(function () {
        Route::get('/', [AccountControllers\DesignationController::class, 'index'])
            // ->middleware('ability:super:*,head:*,account-designation:*,account-designation:view')
            ->name('index');
    });

    Route::name('departments.')->prefix('/accounts/departments')->group(function () {
        Route::get('/', [AccountControllers\DepartmentController::class, 'index'])
            ->middleware('ability:super:*,head:*,account-department:*,account-department:view')
            ->name('index');
        Route::post('/', [AccountControllers\DepartmentController::class, 'store'])
            ->middleware('ability:super:*,account-department:*,account-department:create')
            ->name('store');
        Route::get('/{department}', [AccountControllers\DepartmentController::class, 'show'])
            ->middleware('ability:super:*,account-department:*,account-department:view')
            ->name('show');
        Route::put('/{department}', [AccountControllers\DepartmentController::class, 'update'])
            ->middleware('ability:super:*,account-department:*,account-department:update')
            ->name('update');
        Route::delete('/{department}', [AccountControllers\DepartmentController::class, 'delete'])
            ->middleware('ability:super:*,account-department:*,account-department:delete')
            ->name('delete');
    });

    Route::name('sections.')->prefix('/accounts/sections')->group(function () {
        Route::get('/', [AccountControllers\SectionController::class, 'index'])
            ->middleware('ability:super:*,head:*,account-section:*,account-section:view')
            ->name('index');
        Route::post('/', [AccountControllers\SectionController::class, 'store'])
            ->middleware('ability:super:*,account-section:*,account-section:create')
            ->name('store');
        Route::get('/{section}', [AccountControllers\SectionController::class, 'show'])
            ->middleware('ability:super:*,account-section:*,account-section:view')
            ->name('show');
        Route::put('/{section}', [AccountControllers\SectionController::class, 'update'])
            ->middleware('ability:super:*,account-section:*,account-section:update')
            ->name('update');
        Route::delete('/{section}', [AccountControllers\SectionController::class, 'delete'])
            ->middleware('ability:super:*,account-section:*,account-section:delete')
            ->name('delete');
    });

    Route::name('roles.')->prefix('/accounts/roles')->group(function () {
        Route::get('/', [AccountControllers\RoleController::class, 'index'])
            ->middleware('ability:super:*,head:*,account-role:*,account-role:view')
            ->name('index');
        Route::post('/', [AccountControllers\RoleController::class, 'store'])
            ->middleware('ability:super:*,account-role:*,account-role:create')
            ->name('store');
        Route::get('/{role}', [AccountControllers\RoleController::class, 'show'])
            ->middleware('ability:super:*,account-role:*,account-role:view')
            ->name('show');
        Route::put('/{role}', [AccountControllers\RoleController::class, 'update'])
            ->middleware('ability:super:*,account-role:*,account-role:update')
            ->name('update');
        Route::delete('/{role}', [AccountControllers\RoleController::class, 'delete'])
            ->middleware('ability:super:*,account-role:*,account-role:delete')
            ->name('delete');
    });

    Route::name('users.')->prefix('/accounts/users')->group(function () {
        Route::get('/', [AccountControllers\UserController::class, 'index'])
            ->middleware('ability:super:*,head:*,account-user:*,account-user:view')
            ->name('index');
        Route::post('/', [AccountControllers\UserController::class, 'store'])
            ->middleware('ability:super:*,account-user:*,account-user:create')
            ->name('store');
        Route::get('/{user}', [AccountControllers\UserController::class, 'show'])
            ->middleware('ability:super:*,account-user:*,account-user:view')
            ->name('show');
        Route::put('/{user}', [AccountControllers\UserController::class, 'update'])
            // ->middleware('ability:super:*,account-user:*,account-user:update')
            ->name('update');
        Route::delete('/{user}', [AccountControllers\UserController::class, 'delete'])
            ->middleware('ability:super:*,account-user:*,account-user:delete')
            ->name('delete');
    });
});
