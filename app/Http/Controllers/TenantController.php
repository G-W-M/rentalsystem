<?php

namespace App\Http\Controllers;

use App\Http\Requests\Landlord\AllocateTenantRequest;
use App\Http\Requests\Landlord\StoreTenantRequest;
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

class TenantController extends Controller
{
    /** GET /api/landlord/tenants — tenants belonging to this landlord. */
    public function index(Request $request): JsonResponse
    {
        $tenants = Tenant::where('landlord_id', $request->user()->id)
            ->with('user:id,full_name,email,phone', 'activeOccupancy.unit:id,unit_number,property_id')
            ->get();

        return response()->json($tenants);
    }

    /**
     * POST /api/landlord/tenants
     * Creates the user account AND the tenant profile in one transaction.
     * No self-registration: the landlord creates the tenant.
     */
    public function store(StoreTenantRequest $request): JsonResponse
    {
        $tenant = DB::transaction(function () use ($request) {
            $user = User::create([
                'full_name' => $request->full_name,
                'email'     => $request->email,
                'username'  => $request->username,
                'phone'     => $request->phone,
                'password'  => Hash::make($request->password),
                'role'      => 'tenant',
                'is_active' => true,
            ]);

            return Tenant::create([
                'user_id'           => $user->id,
                'landlord_id'       => $request->user()->id,
                'id_number'         => $request->id_number,
                'nationality'       => $request->nationality,
                'gender'            => $request->gender,
                'employment_status' => $request->employment_status,
                'emergency_contact' => $request->emergency_contact,
                'emergency_phone'   => $request->emergency_phone,
                'is_active'         => true,
            ]);
        });

        return response()->json($tenant->load('user:id,full_name,email'), 201);
    }

    /**
     * POST /api/landlord/tenants/{tenant}/allocate  ({tenant} = user_id)
     * Transaction: create current occupancy, set unit occupied, set move-in date,
     * and generate the first pending rent invoice. Invariants (unit available,
     * tenant has no active occupancy) are enforced by AllocateTenantRequest.
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
     * Transaction: end the current occupancy, free the unit, set move-out date.
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
