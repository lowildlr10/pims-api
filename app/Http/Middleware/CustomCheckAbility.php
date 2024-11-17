<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomCheckAbility
{
    public function handle(Request $request, Closure $next, ...$abilities)
    {
        $user = Auth::user();

        foreach ($abilities as $ability) {
            if ($user && $user->tokenCan($ability)) {
                return $next($request);
            }
        }

        return response()->json([
            'message' => 'Unauthorized access.',
        ], 403);
    }
}
