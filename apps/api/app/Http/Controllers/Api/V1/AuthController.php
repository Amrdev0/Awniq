<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuditLogService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request, AuditLogService $auditLogService): JsonResponse
    {
        $user = User::query()
            ->with(['organization', 'branch', 'roles'])
            ->where('email', $request->string('email')->lower()->toString())
            ->first();

        if (! $user || ! Hash::check($request->string('password')->toString(), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are invalid.'],
            ]);
        }

        if (! $user->isActive()) {
            throw ValidationException::withMessages([
                'email' => ['This user account is not active.'],
            ]);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        $token = $user->createToken($request->string('device_name')->toString() ?: 'api-token')->plainTextToken;

        $auditLogService->record('auth.login', $user, null, ['email' => $user->email], $request);

        return ApiResponse::success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user->refresh()->load(['organization', 'branch', 'roles'])),
        ], 'Logged in successfully.');
    }

    public function logout(Request $request, AuditLogService $auditLogService): JsonResponse
    {
        $auditLogService->record('auth.logout', $request->user(), null, ['email' => $request->user()->email], $request);

        $request->user()->currentAccessToken()?->delete();

        return ApiResponse::success(message: 'Logged out successfully.');
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user()->load(['organization', 'branch', 'roles']));
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($validated);

        return ApiResponse::success(message: 'If the email exists, a password reset link has been sent.');
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset($validated, function (User $user, string $password): void {
            $user->forceFill(['password' => Hash::make($password)])->save();
            $user->tokens()->delete();
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => [__($status)]]);
        }

        return ApiResponse::success(message: 'Password reset successfully.');
    }
}
