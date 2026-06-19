<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_token' => ['nullable', 'string'],
            'platform' => ['nullable', 'in:android,ios'],
        ]);

        $user = \App\Models\User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->errorResponse('Invalid credentials.', 401);
        }

        if (!$user->is_active) {
            return $this->errorResponse('Account is inactive.', 403);
        }

        $user->last_login_at = now();
        $user->save();

        $token = $user->createToken('auth', ['*'])->plainTextToken;

        // Store device token if provided
        if ($request->device_token && $request->platform) {
            \App\Models\DeviceToken::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'token' => $request->device_token,
                ],
                [
                    'platform' => $request->platform,
                    'last_used_at' => now(),
                ]
            );
        }

        return $this->successResponse([
            'user' => new \App\Http\Resources\Api\V1\UserResource($user),
            'token' => $token,
            'type' => 'Bearer',
        ], 'Login successful.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(
            null,
            'Logged out successfully.'
        );
    }

    public function me(): JsonResponse
    {
        $user = Auth::user()->load(['department', 'roles']);

        return $this->successResponse([
            'user' => new \App\Http\Resources\Api\V1\UserResource($user),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'roles' => $user->roles->pluck('name'),
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email'),
            function ($user, $token) {
                // Custom reset URL
                $user->sendPasswordResetNotification($token);
            }
        );

        return $status === Password::RESET_LINK_SENT
            ? $this->successResponse(null, 'Password reset link sent to your email.')
            : $this->errorResponse('Failed to send reset link.');
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset($request->only(
            'email',
            'password',
            'password_confirmation',
            'token'
        ));

        return $status === Password::PASSWORD_RESET
            ? $this->successResponse(null, 'Password reset successful.')
            : $this->errorResponse('Failed to reset password.');
    }
}