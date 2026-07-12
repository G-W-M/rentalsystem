<?php

namespace App\Http\Controllers;

use App\Http\Requests\Landlord\AllocateTenantRequest;
use App\Mail\NewUserCredentialsMail;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\TenantOccupancy;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TenantController extends Controller
{
    /**
     * GET /api/landlord/tenants
     */
    public function index(Request $request): JsonResponse
    {
        $tenants = Tenant::where('landlord_id', $request->user()->id)
            ->with('user:id,full_name,email,phone', 'activeOccupancy.unit:id,unit_number,property_id')
            ->get();

        return response()->json($tenants);
    }

    /**
     * POST /api/landlord/tenants
     * Password is auto-generated and emailed to the tenant via
     * NewUserCredentialsMail. If mail delivery fails (e.g. SMTP
     * unreachable), the tenant account is still created and the
     * password is returned in the JSON response as a fallback so the
     * landlord isn't stuck.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'full_name'         => ['required', 'string', 'max:100'],
            'email'             => ['required', 'email', 'max:100', Rule::unique('users', 'email')],
            'username'          => ['required', 'string', 'max:50', Rule::unique('users', 'username')],
            'phone'             => ['nullable', 'string', 'max:20'],
            'id_number'         => ['nullable', 'string', 'max:50'],
            'nationality'       => ['nullable', 'string', 'max:100'],
            'gender'            => ['nullable', 'in:male,female,other'],
            'employment_status' => ['nullable', 'in:employed,self-employed,student,retired'],
            'emergency_contact' => ['nullable', 'string', 'max:100'],
            'emergency_phone'   => ['nullable', 'string', 'max:20'],
        ]);

        $generatedPassword = Str::random(10);

        $tenant = DB::transaction(function () use ($data, $request, $generatedPassword) {
            $user = User::create([
                'full_name' => $data['full_name'],
                'email'     => $data['email'],
                'username'  => $data['username'],
                'phone'     => $data['phone'] ?? null,
                'password'  => Hash::make($generatedPassword),
                'role'      => 'tenant',
                'is_active' => true,
            ]);

            return Tenant::create([
                'user_id'           => $user->id,
                'landlord_id'       => $request->user()->id,
                'id_number'         => $data['id_number'] ?? null,
                'nationality'       => $data['nationality'] ?? null,
                'gender'            => $data['gender'] ?? null,
                'employment_status' => $data['employment_status'] ?? null,
                'emergency_contact' => $data['emergency_contact'] ?? null,
                'emergency_phone'   => $data['emergency_phone'] ?? null,
                'is_active'         => true,
            ]);
        });

        try {
            Mail::to($data['email'])->send(new NewUserCredentialsMail(
                fullName: $data['full_name'],
                email: $data['email'],
                username: $data['username'],
                password: $generatedPassword,
                role: 'tenant',
                loginUrl: url('/login'),
            ));

            return response()->json([
                'tenant'  => $tenant->load('user:id,full_name,email'),
                'message' => 'Tenant created. Login credentials have been emailed to '.$data['email'].'.',
            ], 201);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'tenant'   => $tenant->load('user:id,full_name,email'),
                'password' => $generatedPassword,
                'message'  => 'Tenant created, but the credentials email failed to send. Share this password with them directly.',
            ], 201);
        }
    }

    /**
     * POST /api/landlord/tenants/{tenant}/allocate
     */
    public function allocate(AllocateTenantRequest $request, int $tenant): JsonResponse
    {
        $this->ownTenant($request, $tenant);

        $unit = Unit::findOrFail((int) $request->input('unit_id'));
        $this->ownUnit($request, $unit);

        $occupancy = DB::transaction(function () use ($request, $tenant, $unit) {
            $occ = TenantOccupancy::create([
                'tenant_id'            => $tenant,
                'unit_id'              => $unit->id,
                'start_date'           => $request->start_date,
                'is_current'           => true,
                'rent_amount_at_start' => $unit->rent_amount,
                'deposit_amount'       => $request->input('deposit_amount'),
                'deposit_paid'         => $request->boolean('deposit_paid'),
                'created_by'           => $request->user()->id,
            ]);

            $unit->update(['status' => 'occupied']);

            Tenant::where('user_id', $tenant)->update(['moved_in_date' => $request->start_date]);

            Payment::create([
                'tenant_id' => $tenant,
                'unit_id'   => $unit->id,
                'amount'    => $unit->rent_amount,
                'due_date'  => Carbon::parse($request->start_date)->addMonthNoOverflow(),
                'status'    => 'pending',
                'notes'     => 'Auto-generated first rent invoice on allocation.',
            ]);

            return $occ;
        });

        return response()->json([
            'message'   => 'Tenant allocated.',
            'occupancy' => $occupancy->load('unit:id,unit_number,property_id'),
        ], 201);
    }

    /**
     * POST /api/landlord/tenants/{tenant}/deallocate
     */
    public function deallocate(Request $request, int $tenant): JsonResponse
    {
        $this->ownTenant($request, $tenant);

        $request->validate([
            'end_date'           => ['required', 'date'],
            'termination_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $occupancy = TenantOccupancy::where('tenant_id', $tenant)
            ->where('is_current', true)
            ->first();

        if ($occupancy === null) {
            return response()->json(['message' => 'No active occupancy to end.'], 422);
        }

        DB::transaction(function () use ($occupancy, $request, $tenant) {
            $occupancy->update([
                'is_current'         => false,
                'end_date'           => $request->end_date,
                'termination_reason' => $request->termination_reason,
            ]);

            Unit::where('id', $occupancy->unit_id)->update(['status' => 'available']);

            Tenant::where('user_id', $tenant)->update(['moved_out_date' => $request->end_date]);
        });

        return response()->json(['message' => 'Tenant deallocated.']);
    }

    private function ownTenant(Request $request, int $tenantId): void
    {
        $owns = Tenant::where('user_id', $tenantId)
            ->where('landlord_id', $request->user()->id)
            ->exists();

        abort_unless($owns, 403, 'That tenant is not yours.');
    }

    private function ownUnit(Request $request, Unit $unit): void
    {
        $owns = $unit->property()->where('landlord_id', $request->user()->id)->exists();

        abort_unless($owns, 403, 'That unit is not yours.');
    }
}
