<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\LogRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    public function __construct(
        protected LogRepository $logRepository
    ) {}

    public function me(Request $request): User
    {
        return User::with([
            'department:id,department_name',
            'section:id,section_name',
            'position:id,position_name',
            'designation:id,designation_name',
            'roles:id,role_name',
        ])->find($request->user()->id);
    }

    public function login(array $credentials): array
    {
        $loginField = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL)
            ? 'email' : 'username';

        $loginCredentials = [
            $loginField => $credentials['login'],
            'password' => $credentials['password'],
        ];

        if (! Auth::attempt($loginCredentials)) {
            $this->logRepository->create([
                'message' => 'Login attempt unsuccessful.',
                'details' => 'Invalid credentials.',
                'log_module' => 'login',
                'data' => ['login' => $credentials['login']],
            ], isError: true);

            return [
                'success' => false,
                'message' => 'Invalid credentials.',
                'status' => 401,
            ];
        }

        if (Auth::user()->restricted) {
            $this->logRepository->create([
                'message' => 'Login attempt unsuccessful.',
                'details' => 'User is restricted.',
                'log_module' => 'login',
                'data' => ['login' => $credentials['login']],
            ], isError: true);

            Auth::logout();

            return [
                'success' => false,
                'message' => 'User is not active. Please contact your system administrator.',
                'status' => 401,
            ];
        }

        $abilities = Auth::user()->permissions();
        $token = Auth::user()
            ->createToken('authToken', $abilities, now()->addDay())
            ->plainTextToken;

        $this->logRepository->create([
            'message' => 'Logged in successfully.',
            'log_module' => 'login',
            'data' => Auth::user(),
        ]);

        return [
            'success' => true,
            'access_token' => $token,
            'message' => 'Logged in successfully.',
        ];
    }

    public function refreshToken(User $user): array
    {
        try {
            $currentToken = $user->currentAccessToken();
            $abilities = $user->permissions();

            $currentToken?->delete();

            $newToken = $user->createToken('authToken', $abilities, now()->addDay())->plainTextToken;

            $this->logRepository->create([
                'message' => 'Token refreshed successfully.',
                'log_module' => 'login',
                'data' => $user,
            ]);

            return [
                'success' => true,
                'access_token' => $newToken,
                'message' => 'Token refreshed successfully.',
            ];
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => 'Token refresh failed.',
                'details' => $th->getMessage(),
                'log_module' => 'login',
                'data' => $user,
            ], isError: true);

            return [
                'success' => false,
                'message' => 'Failed to refresh token. Please try again.',
                'status' => 422,
            ];
        }
    }

    public function logout(User $user): array
    {
        try {
            $user->currentAccessToken()->delete();

            $this->logRepository->create([
                'message' => 'Logged out successfully.',
                'log_module' => 'logout',
                'data' => $user,
            ]);

            return [
                'success' => true,
                'message' => 'Logged out successfully.',
            ];
        } catch (\Throwable $th) {
            $this->logRepository->create([
                'message' => 'Logout failed.',
                'details' => $th->getMessage(),
                'log_module' => 'logout',
                'data' => $user,
            ], isError: true);

            return [
                'success' => false,
                'message' => 'Logout failed. Please try again.',
                'status' => 422,
            ];
        }
    }
}
