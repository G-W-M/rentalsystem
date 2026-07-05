<?php

namespace App\Http\Controllers;

use App\Models\Caretaker;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class LandlordController extends Controller
{
    /** GET /api/landlord/caretakers — caretakers employed by this landlord. */
    public function caretakers(Request $request): JsonResponse
    {
        $caretakers = Caretaker::where('landlord_id', $request->user()->id)
            ->with('user:id,full_name,email,phone')
            ->get();

        return response()->json($caretakers);
    }

    /**
     * POST /api/landlord/caretakers
     * Creates a caretaker user + profile under this landlord (no self-registration).
     */
    public function storeCaretaker(Request $request): JsonResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:100'],
            'email'     => ['required', 'email', 'max:100', Rule::unique('users', 'email')],
            'username'  => ['required', 'string', 'max:50', Rule::unique('users', 'username')],
            'phone'     => ['nullable', 'string', 'max:20'],
            'password'  => ['required', 'string', 'min:6'],
            'id_number' => ['nullable', 'string', 'max:50'],
        ]);

        $caretaker = DB::transaction(function () use ($data, $request) {
            $user = User::create([
                'full_name' => $data['full_name'],
                'email'     => $data['email'],
                'username'  => $data['username'],
                'phone'     => $data['phone'] ?? null,
                'password'  => Hash::make($data['password']),
                'role'      => 'caretaker',
                'is_active' => true,
            ]);

            return Caretaker::create([
                'user_id'     => $user->id,
                'landlord_id' => $request->user()->id,
                'id_number'   => $data['id_number'] ?? null,
                'is_active'   => true,
                'hire_date'   => now()->toDateString(),
            ]);
        });

        return response()->json($caretaker->load('user:id,full_name,email'), 201);
    }
}
