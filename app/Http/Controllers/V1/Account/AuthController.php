<?php

namespace App\Http\Controllers\V1\Account;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Authentication
 * APIs for user authentication
 */
class AuthController extends Controller
{
    public function __construct(
        protected AuthService $service
    ) {}

    /**
     * Get Current User
     *
     * Get the authenticated user's profile.
     *
     * @response 200 {
     *   "data": {
     *     "user": {...},
     *     "permissions": [...]
     *   }
     * }
     */
    public function me(Request $request): JsonResponse
    {
        $user = $this->service->me($request);

        return response()->json([
            'data' => [
                'user' => new UserResource($user),
                'permissions' => $request->user()->permissions(),
            ],
        ]);
    }

    /**
     * Login
     *
     * Authenticate a user and generate an access token.
     *
     * @bodyParam login string required User's email or username.
     * @bodyParam password string required User's password.
     *
     * @response 200 {
     *   "data": {
     *     "access_token": "token",
     *     "message": "Logged in successfully."
     *   }
     * }
     * @response 401 {
     *   "message": "Invalid credentials."
     * }
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string|min:6',
        ]);

        $result = $this->service->login($validated);

        if (! $result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], $result['status']);
        }

        return response()->json([
            'data' => [
                'access_token' => $result['access_token'],
                'message' => $result['message'],
            ],
        ]);
    }

    /**
     * Refresh Token
     *
     * Refresh the current access token.
     *
     * @response 200 {
     *   "data": {
     *     "access_token": "new_token",
     *     "message": "Token refreshed successfully."
     *   }
     * }
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $result = $this->service->refreshToken($request->user());

        if (! $result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], $result['status']);
        }

        return response()->json([
            'data' => [
                'access_token' => $result['access_token'],
                'message' => $result['message'],
            ],
        ]);
    }

    /**
     * Logout
     *
     * Logout the current user and delete the access token.
     *
     * @response 200 {
     *   "message": "Logged out successfully."
     * }
     */
    public function logout(Request $request): JsonResponse
    {
        $result = $this->service->logout($request->user());

        if (! $result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], $result['status']);
        }

        return response()->json([
            'message' => $result['message'],
        ]);
    }
}
