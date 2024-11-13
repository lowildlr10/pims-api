<?php

namespace App\Http\Controllers\V1\Account;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Get the current user
    public function me(Request $request)
    {
        $user = User::with('position', 'designation', 'department', 'section')
            ->find($request->user()->id);

        return response()->json([
            'data' => [
                'id' => $user->id,
                'fullname' => $user->fullname,
                'firstname' => $user->firstname,
                'middlename' => $user->middlename,
                'lastname' => $user->lastname,
                'username' => $user->username,
                'email' => $user->email,
                'phone' => $user->phone,
                'position' => $user->position->only(['id', 'position_name']),
                'designation' => $user->designation->only(['id', 'designation_name']),
                'department' => $user->department->only(['id', 'department_name']),
                'section' => $user->section->only(['id', 'section_name']),
                'avatar' => $user->avatar,
                'signature' => $user->signature,
            ]
        ]);
    }

    public function login(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'login' => 'required',
            'password' => 'required|min:6'
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
            ->createToken('procsysToken', $abilities, now()->addDay())
            ->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'message' => 'Logged in successfully',
        ]);
    }

    // Logout a user
    public function logout(Request $request)
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
