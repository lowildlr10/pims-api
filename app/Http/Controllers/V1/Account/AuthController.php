<?php

namespace App\Http\Controllers\V1\Account;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        $user = User::with([
            'division:id,division_name',
            'section:id,section_name',
            'position:id,position_name',
            'designation:id,designation_name',
            'roles:id,role_name'
        ])
        ->find($request->user()->id);

        return response()->json([
            'data' => [
                'user' => $user,
                'permissions' => $request->user()->permissions()
            ]
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        // Validate the request
        $validated = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string|min:6'
        ]);

        $loginField = filter_var($validated['login'], FILTER_VALIDATE_EMAIL)
            ? 'email' : 'username';

        $credentials = [
            $loginField => $validated['login'],
            'password' => $validated['password']
        ];

        // Attempt to log the user in
        if (!auth()->attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        if (auth()->user()->restricted) {
            return response()->json([
                'message' => 'User is not active. Please contact your system administrator.',
            ], 401);
        }

        // Generate a token for the user
        $abilities = auth()->user()->permissions();
        $token = auth()->user()
            ->createToken('authToken', $abilities, now()->addDay())
            ->plainTextToken;

        return response()->json([
            'data' => [
                'access_token' => $token,
                'message' => 'Logged in successfully',
            ]
        ]);
    }

    // Logout a user
    public function logout(Request $request): JsonResponse
    {
        try {
            // Revoke the user's token
            $request->user()->currentAccessToken()->delete();
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Logout failed. Please try again.',
            ], 422);
        }

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
