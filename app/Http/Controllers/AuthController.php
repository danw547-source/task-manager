<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\AccessToken;

/**
 * Handles API auth endpoints.
 * Keeping this separate makes login/register flows easy to find and maintain.
 */
class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Registers a new account with the default `user` role.
     *
        * Passport in plain terms:
        * - When a user logs in or registers, Passport creates an access token for that user.
        * - The frontend stores that token and sends it as `Authorization: Bearer <token>`.
        * - Protected routes use `auth:api` to read that token and identify the user.
     */

    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'user',
        ]);

        $token = $user->createToken('auth_token')->accessToken;

        return $this->successResponse([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 'User registered successfully', 201);
    }

    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return $this->errorResponse('Invalid credentials', 401);
        }

        $token = $user->createToken('auth_token')->accessToken;

        return $this->successResponse([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 'Login successful');
    }

    public function me(Request $request)
    {
        return $this->successResponse($request->user(), 'Authenticated user retrieved successfully');
    }

    public function logout(Request $request)
    {
        $token = $request->user()?->token();

        if ($token instanceof AccessToken) {
            $token->revoke();
        }

        return $this->successResponse(null, 'Logged out successfully');
    }
}
