<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Models\SessionLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Sanctum token auth. This is our "JWT" for the PWA / mobile clients.
 * OWNER: Dev B. Ships first (H0-3) because everything role-scoped depends on it.
 */
class AuthController extends Controller
{
    /**
     * POST /api/login
     * Returns a Sanctum plain-text token the client stores locally (for offline queue replay).
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'This account is inactive. Contact your administrator.'], 403);
        }

        // One token per device; name it by device type sent from client (defaults to 'pwa').
        $device = $request->input('device_type', 'pwa');
        $token  = $user->createToken($device)->plainTextToken;

        $user->forceFill(['last_login' => now()])->save();

        // Session/activity log for compliance (IP, UA, timestamp).
        SessionLog::create([
            'user_id'       => $user->id,
            'session_token' => Str::random(40),
            'device_type'   => in_array($device, ['web', 'mobile', 'api']) ? $device : 'mobile',
            'login_time'    => now(),
            'payload'       => json_encode(['login' => true]),
            'is_active'     => true,
            'ip_address'    => $request->ip(),
            'user_agent'    => (string) $request->userAgent(),
            'last_activity' => now()->timestamp,
        ]);

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'        => $user->id,
                'full_name' => $user->full_name,
                'email'     => $user->email,
                'role'      => $user->role,
            ],
        ]);
    }

    /** POST /api/logout — revokes the current token only. */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        SessionLog::where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->update(['is_active' => false, 'logout_time' => now()]);

        return response()->json(['message' => 'Logged out.']);
    }

    /** GET /api/me — current authenticated user (used by PWA on boot to restore session). */
    public function me(Request $request): JsonResponse
    {
        $u = $request->user();

        return response()->json([
            'id'        => $u->id,
            'full_name' => $u->full_name,
            'email'     => $u->email,
            'role'      => $u->role,
        ]);
    }

    /** POST /api/forgot-password — rate-limited; dispatches reset mail asynchronously. */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        // Standard Laravel broker; queue the mail (config/queue.php).
        $status = \Illuminate\Support\Facades\Password::sendResetLink(
            $request->only('email')
        );

        return response()->json(['message' => __($status)]);
    }

    /** POST /api/reset-password */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:6'],
        ]);

        $status = \Illuminate\Support\Facades\Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
                $user->tokens()->delete(); // force re-login everywhere
            }
        );

        return response()->json(['message' => __($status)]);
    }
}