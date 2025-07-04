<?php

namespace App\Http\Controllers\V1\Account;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\LogRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private LogRepository $logRepository;

    public function __construct(LogRepository $logRepository)
    {
        $this->logRepository = $logRepository;
    }

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
            $this->logRepository->create([
                'message' => "Login attempt unsuccessful.",
                'details' => 'Invalid credentials.',
                'log_module' => 'login',
                'data' => [
                    'login' => $validated['login']
                ]
            ], isError: true);

            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        if (auth()->user()->restricted) {
            $this->logRepository->create([
                'message' => "Login attempt unsuccessful.",
                'details' => 'User is restricted.',
                'log_module' => 'login',
                'data' => [
                    'login' => $validated['login']
                ]
            ], isError: true);

            return response()->json([
                'message' => 'User is not active. Please contact your system administrator.',
            ], 401);
        }

        // Generate a token for the user
        $abilities = auth()->user()->permissions();
        $token = auth()->user()
            ->createToken('authToken', $abilities, now()->addDay())
            ->plainTextToken;

        $this->logRepository->create([
            'message' => "Logged in successfully.",
            'log_module' => 'login',
            'data' => auth()->user()
        ]);

        return response()->json([
            'data' => [
                'access_token' => $token,
                'message' => 'Logged in successfully.',
            ]
        ]);
    }
    
    // Renew login token of a user
    public function refreshToken(Request $request): JsonResponse
    {
        $user = $request->user();

        try {
            // Get current token and its abilities
            $currentToken = $user->currentAccessToken();
            $abilities = $user->permissions();

            // Delete current token
            $currentToken?->delete();

            // Generate new token valid for 1 day
            $newToken = $user->createToken('authToken', $abilities, now()->addDay())->plainTextToken;

            // Log the token refresh
            $this->logRepository->create([
                'message' => "Token refreshed successfully.",
                'log_module' => 'login',
                'data' => $user
            ]);

            return response()->json([
                'data' => [
                    'access_token' => $newToken,
                    'message' => 'Token refreshed successfully.'
                ]
            ]);
        } catch (\Throwable $th) {
            // Log the error
            $this->logRepository->create([
                'message' => "Token refresh failed.",
                'details' => $th->getMessage(),
                'log_module' => 'login',
                'data' => $user
            ], isError: true);

            return response()->json([
                'message' => 'Failed to refresh token. Please try again.',
            ], 422);
        }
    }

    // Logout a user
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        try {
            $request->user()->currentAccessToken()->delete();

            $this->logRepository->create([
                'message' => "Logged out successfully.",
                'log_module' => 'logout',
                'data' => $user
            ]);
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => "Logout failed.",
                'details' => $th->getMessage(),
                'log_module' => 'logout',
                'data' => $user
            ], isError: true);

            return response()->json([
                'message' => 'Logout failed. Please try again.',
            ], 422);
        }

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
