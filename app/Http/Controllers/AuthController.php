<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * POST /api/login
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
     * POST /api/register
     * Free landlord self-registration. Creates the User + Landlord profile
     * in one transaction, active immediately. Tenants/caretakers still can
     * NEVER self-register — this endpoint only ever creates role=landlord.
     */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:100'],
            'email'     => ['required', 'email', 'max:100', 'unique:users,email'],
            'username'  => ['required', 'string', 'max:50', 'unique:users,username'],
            'phone'     => ['nullable', 'string', 'max:20'],
            'password'  => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'full_name' => $data['full_name'],
                'email'     => $data['email'],
                'username'  => $data['username'],
                'phone'     => $data['phone'] ?? null,
                'password'  => Hash::make($data['password']),
                'role'      => 'landlord',
                'is_active' => true,
            ]);

            \App\Models\Landlord::create([
                'user_id'           => $user->id,
                'registration_date' => now(),
            ]);

            return $user;
        });

        Auth::guard('web')->login($user, true);
        $request->session()->regenerate();

        $token = $user->createToken('pwa')->plainTextToken;

        return response()->json([
            'message' => 'Account created.',
            'token'   => $token,
            'user'    => $this->userPayload($user),
        ], 201);
    }

    /**
     * POST /api/logout
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

    public function me(Request $request): JsonResponse
    {
        return response()->json($this->userPayload($request->user()));
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:100'],
            'phone'     => ['nullable', 'string', 'max:20'],
            'email'     => ['required', 'email', 'max:150', 'unique:users,email,' . $user->id],
        ]);

        $user->update($data);

        return response()->json([
            'message' => 'Profile updated.',
            'user'    => $this->userPayload($user->fresh()),
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 422);
        }

        $user->update(['password' => Hash::make($data['password'])]);

        return response()->json(['message' => 'Password updated.']);
    }

    /**
     * POST /api/forgot-password
     * No mail service is configured in this environment. Instead of sending
     * an email, this generates a real Laravel password-reset token and
     * returns the reset URL directly in the JSON response, so the frontend
     * can display it on-screen. This is a pragmatic dev-environment
     * substitute — swap for Password::sendResetLink alone once a real mail
     * driver is configured, and stop returning the URL.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $request->email)->first();

        if ($user === null) {
            return response()->json([
                'message'   => 'If that email exists, a reset link has been generated.',
                'reset_url' => null,
            ]);
        }

        $token = Password::broker()->createToken($user);
        $resetUrl = url('/reset-password?token=' . $token . '&email=' . urlencode($user->email));

        return response()->json([
            'message'   => 'Reset link generated. Since no mail service is configured, use the link below directly.',
            'reset_url' => $resetUrl,
        ]);
    }

    /**
     * POST /api/reset-password
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