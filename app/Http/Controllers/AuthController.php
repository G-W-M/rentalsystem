<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * POST /api/login
     * Same-origin cookie session for the web/PWA, plus a Sanctum bearer token
     * returned for native clients and offline-queue replay.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if ($user === null || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'Account inactive. Contact your administrator.',
            ], 403);
        }

        Auth::guard('web')->login($user, true);
        $request->session()->regenerate();

        $device = $request->input('device_type', 'pwa');
        $token  = $user->createToken($device)->plainTextToken;

        $user->forceFill(['last_login' => now()])->save();

        return response()->json([
            'token' => $token,
            'user'  => $this->userPayload($user),
        ]);
    }

    /**
     * POST /api/logout
     * Revokes the current bearer token and clears the web session.
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();
        if ($token !== null) {
            $token->delete();
        }

        Auth::guard('web')->logout();
        $request->session()?->invalidate();
        $request->session()?->regenerateToken();

        return response()->json(['message' => 'Logged out.']);
    }

    /**
     * GET /api/me
     * Returns the authenticated user; used by the PWA to restore session on boot.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json($this->userPayload($request->user()));
    }

    /**
     * POST /api/forgot-password
     * Sends a password reset link using Laravel's password broker.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        return response()->json(['message' => __($status)]);
    }

    /**
     * POST /api/reset-password
     * Completes the reset. Invalidates all existing tokens so a compromised
     * session cannot persist after a password change.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:6'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
                $user->tokens()->delete();
            }
        );

        return response()->json(['message' => __($status)]);
    }

    private function userPayload(User $user): array
    {
        return [
            'id'        => $user->id,
            'full_name' => $user->full_name,
            'email'     => $user->email,
            'role'      => $user->role,
            'phone'     => $user->phone,
        ];
    }
}
