<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

const API_VERSION_1 = 'v1';

Route::prefix(API_VERSION_1)->group(function() {
    $v1RouteFiles = glob(base_path('routes/' . API_VERSION_1 . '/*/routes.php'));

    foreach ($v1RouteFiles as $routeFile) {
        require $routeFile;
    }
});

Route::get('/unauthenticated', function() {
    return response()->json([
        'message' => 'Not logged in.',
    ], 401);
});
