<?php

namespace App\Http\Controllers;

use App\Models\Caretaker;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LandlordController extends Controller
{
    /**
     * GET /api/landlord/caretakers
     */
    public function caretakers(Request $request): JsonResponse
    {
        $caretakers = Caretaker::where('landlord_id', $request->user()->id)
            ->with('user:id,full_name,email,phone', 'property:id,name')
            ->get();

        return response()->json($caretakers);
    }

    /**
     * POST /api/landlord/caretakers
     * Creates a caretaker user + profile under this landlord, OPTIONALLY
     * assigned to one specific property (1:1 — enforced by the unique
     * index on caretakers.property_id; a 422 with a clear message is
     * returned if the chosen property already has a caretaker).
     *
     * Password is auto-generated (not landlord-typed) and returned in the
     * response so the landlord can relay it — no real email service is
     * configured, so this is the on-screen dev-mode equivalent of "auto
     * email credentials."
     */
    public function storeCaretaker(Request $request): JsonResponse
    {
        $data = $request->validate([
            'full_name'   => ['required', 'string', 'max:100'],
            'email'       => ['required', 'email', 'max:100', Rule::unique('users', 'email')],
            'username'    => ['required', 'string', 'max:50', Rule::unique('users', 'username')],
            'phone'       => ['nullable', 'string', 'max:20'],
            'id_number'   => ['nullable', 'string', 'max:50'],
            'property_id' => ['nullable', 'integer', 'exists:properties,id'],
        ]);

        if (! empty($data['property_id'])) {
            $ownsProperty = Property::where('id', $data['property_id'])
                ->where('landlord_id', $request->user()->id)
                ->exists();

            abort_unless($ownsProperty, 403, 'That property is not yours.');

            $alreadyAssigned = Caretaker::where('property_id', $data['property_id'])->exists();

            if ($alreadyAssigned) {
                return response()->json([
                    'message' => 'This property already has a caretaker assigned. Reassign or remove the existing one first.',
                ], 422);
            }
        }

        $generatedPassword = Str::random(10);

        $caretaker = DB::transaction(function () use ($data, $request, $generatedPassword) {
            $user = User::create([
                'full_name' => $data['full_name'],
                'email'     => $data['email'],
                'username'  => $data['username'],
                'phone'     => $data['phone'] ?? null,
                'password'  => Hash::make($generatedPassword),
                'role'      => 'caretaker',
                'is_active' => true,
            ]);

            return Caretaker::create([
                'user_id'     => $user->id,
                'landlord_id' => $request->user()->id,
                'property_id' => $data['property_id'] ?? null,
                'id_number'   => $data['id_number'] ?? null,
                'is_active'   => true,
                'hire_date'   => now()->toDateString(),
            ]);
        });

        return response()->json([
            'caretaker' => $caretaker->load('user:id,full_name,email', 'property:id,name'),
            'password'  => $generatedPassword,
            'message'   => 'Caretaker created. Share these credentials with them directly (no email service configured).',
        ], 201);
    }

    /**
     * PUT /api/landlord/caretakers/{caretaker}/assign-property
     * Reassign (or unassign, with property_id=null) a caretaker to a
     * property. Enforces the same 1:1 uniqueness check.
     */
    public function assignCaretakerToProperty(Request $request, int $caretaker): JsonResponse
    {
        $record = Caretaker::where('user_id', $caretaker)
            ->where('landlord_id', $request->user()->id)
            ->first();

        abort_unless($record !== null, 404, 'Caretaker not found.');

        $data = $request->validate([
            'property_id' => ['nullable', 'integer', 'exists:properties,id'],
        ]);

        if (! empty($data['property_id'])) {
            $ownsProperty = Property::where('id', $data['property_id'])
                ->where('landlord_id', $request->user()->id)
                ->exists();

            abort_unless($ownsProperty, 403, 'That property is not yours.');

            $alreadyAssigned = Caretaker::where('property_id', $data['property_id'])
                ->where('user_id', '!=', $caretaker)
                ->exists();

            if ($alreadyAssigned) {
                return response()->json([
                    'message' => 'This property already has a different caretaker assigned.',
                ], 422);
            }
        }

        $record->update(['property_id' => $data['property_id'] ?? null]);

        return response()->json([
            'message'   => 'Caretaker assignment updated.',
            'caretaker' => $record->fresh()->load('user:id,full_name', 'property:id,name'),
        ]);
    }
}