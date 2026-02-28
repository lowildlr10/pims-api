<?php

use Illuminate\Support\Facades\Route;

require __DIR__.'/v1/api.php';

Route::get('/unauthenticated', function () {
    return response()->json([
        'message' => 'Not logged in.',
    ], 401);
});
