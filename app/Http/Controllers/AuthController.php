<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * POST /api/login
     *
     * PWA-only auth. Single mechanism: Sanctum stateful session via
     * Auth::attempt() + session regenerate. Requires ->statefulApi() in
     * bootstrap/app.php and the browsed host listed in
     * SANCTUM_STATEFUL_DOMAINS.
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

        Auth::login($user, true);
        $request->session()->regenerate();

        $user->forceFill(['last_login' => now()])->save();

        return response()->json([
            'user' => $this->userPayload($user),
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

        Auth::login($user, true);
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Account created.',
            'user'    => $this->userPayload($user),
        ], 201);
    }

    /**
     * POST /logout
     *
     * Triggered by a plain Blade <form method="POST"> in the sidebar/navbar
     * (not a JS fetch call), so this redirects rather than returning JSON —
     * returning JSON here previously rendered the raw
     * {"message":"Logged out."} text as a blank page after a full-page
     * form submission.
     */
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()?->invalidate();
        $request->session()?->regenerateToken();

        if ($request->wantsJson() && ! $request->hasSession()) {
            return response()->json(['message' => 'Logged out.']);
        }

        return redirect()->route('login');
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
     * Generates a real Laravel password-reset token and emails the reset
     * URL via PasswordResetMail, instead of returning it in the JSON
     * response. Same response is returned whether or not the email
     * exists, to avoid leaking which addresses have accounts.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $request->email)->first();

        if ($user === null) {
            return response()->json([
                'message' => 'If that email exists, a reset link has been sent.',
            ]);
        }

        $token = Password::createToken($user);
        $resetUrl = url('/reset-password?token=' . $token . '&email=' . urlencode($user->email));

        try {
            Mail::to($user->email)->send(new PasswordResetMail(
                fullName: $user->full_name,
                resetUrl: $resetUrl,
            ));
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'We could not send the reset email right now. Please try again shortly or contact support.',
            ], 500);
        }

        return response()->json([
            'message' => 'If that email exists, a reset link has been sent.',
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
