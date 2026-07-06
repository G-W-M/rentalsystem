<?php

namespace App\Http\Controllers;

use App\Models\Caretaker;
use App\Models\Landlord;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\TenantOccupancy;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Admin user management (FR 6.2). Simplified per stabilization scope: no
 * email dispatch (no mail service configured) — user creation still returns
 * a generated temporary password directly, but the admin-triggered password
 * reset action lets the admin set a specific new password with confirmation,
 * rather than generating one.
 */
class AdminController extends Controller
{
    /**
     * GET /api/admin/users
     * Supports filters: role, status (active/inactive).
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }
        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        return response()->json($query->latest()->paginate(20));
    }

    /**
     * POST /api/admin/users
     * Creates a user + role profile.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:100'],
            'email'     => ['required', 'email', 'max:100', 'unique:users,email'],
            'username'  => ['required', 'string', 'max:50', 'unique:users,username'],
            'phone'     => ['nullable', 'string', 'max:20'],
            'role'      => ['required', 'in:admin,landlord,caretaker,tenant'],
            'landlord_id' => ['required_if:role,caretaker,tenant', 'nullable', 'integer', 'exists:landlords,user_id'],
        ]);

        $generatedPassword = Str::random(10);

        $user = DB::transaction(function () use ($data, $generatedPassword) {
            $user = User::create([
                'full_name' => $data['full_name'],
                'email'     => $data['email'],
                'username'  => $data['username'],
                'phone'     => $data['phone'] ?? null,
                'password'  => Hash::make($generatedPassword),
                'role'      => $data['role'],
                'is_active' => true,
            ]);

            switch ($data['role']) {
                case 'landlord':
                    Landlord::create(['user_id' => $user->id, 'registration_date' => now()]);
                    break;
                case 'caretaker':
                    Caretaker::create([
                        'user_id'     => $user->id,
                        'landlord_id' => $data['landlord_id'],
                        'is_active'   => true,
                        'hire_date'   => now()->toDateString(),
                    ]);
                    break;
                case 'tenant':
                    Tenant::create([
                        'user_id'     => $user->id,
                        'landlord_id' => $data['landlord_id'],
                        'is_active'   => true,
                    ]);
                    break;
            }

            return $user;
        });

        return response()->json([
            'message'  => 'User created.',
            'user'     => $user,
            'password' => $generatedPassword,
        ], 201);
    }

    /**
     * PUT /api/admin/users/{user}
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:100'],
            'phone'     => ['nullable', 'string', 'max:20'],
            'is_active' => ['required', 'boolean'],
        ]);

        $user->update($data);

        return response()->json(['message' => 'User updated.', 'user' => $user->fresh()]);
    }

    /**
     * DELETE /api/admin/users/{user}
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        $request->validate(['reason' => ['required', 'string', 'max:255']]);

        abort_if($user->id === $request->user()->id, 422, 'You cannot delete your own account.');

        if ($user->role === 'landlord') {
            $hasProperties = Property::where('landlord_id', $user->id)->exists();
            if ($hasProperties) {
                return response()->json(['message' => 'Cannot delete: this landlord has active properties.'], 422);
            }
        }

        if ($user->role === 'tenant') {
            $hasOccupancy = TenantOccupancy::where('tenant_id', $user->id)->where('is_current', true)->exists();
            if ($hasOccupancy) {
                return response()->json(['message' => 'Cannot delete: this tenant has an active occupancy.'], 422);
            }
        }

        if ($user->role === 'caretaker') {
            $hasTasks = Task::where('assigned_to', $user->id)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->exists();
            if ($hasTasks) {
                return response()->json(['message' => 'Cannot delete: this caretaker has active tasks.'], 422);
            }
        }

        DB::table('audit_trails')->insert([
            'user_id'    => $request->user()->id,
            'action'     => 'Deleted user #' . $user->id . ' (' . $user->email . '). Reason: ' . $request->input('reason'),
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user->delete();

        return response()->json(['message' => 'User deleted.']);
    }

    /**
     * POST /api/admin/users/{user}/reset-password
     * Admin sets a SPECIFIC new password (with confirmation), rather than
     * the system generating one.
     */
    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user->update(['password' => Hash::make($data['password'])]);
        $user->tokens()->delete();

        return response()->json(['message' => 'Password reset successfully.']);
    }
}
