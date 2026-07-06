<?php

namespace App\Http\Controllers;

use App\Models\Landlord;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * SHARED FILE — owned jointly across the role split. Four methods, four
 * roles. Never overwrite this file with a version containing only some of
 * these methods — see ASSEMBLY.md for the full history of why this matters.
 *
 * IMPORTANT: every Cache::remember() closure here must return PLAIN ARRAYS,
 * not Eloquent Collections. Collections cached via the file/database driver
 * can come back as unserializable __PHP_Incomplete_Class_Name stubs on
 * certain PHP/cache-driver combinations. Always ->toArray()/->values() any
 * Collection before it leaves a cache closure.
 */
class DashboardController extends Controller
{
    /**
     * GET /api/admin/dashboard
     * Global, unscoped platform stats (per FR 6.1).
     */
    public function admin(Request $request): JsonResponse
    {
        $data = Cache::remember('admin_dashboard', 300, function () {
            $propertyDistribution = Property::select('property_type', DB::raw('count(*) as total'))
                ->groupBy('property_type')
                ->get()
                ->map(fn ($r) => ['type' => $r->property_type, 'total' => (int) $r->total])
                ->values()
                ->toArray();

            $topLandlords = Landlord::withSum(
                    ['properties as revenue' => function ($q) {
                        $q->join('units', 'units.property_id', '=', 'properties.id')
                          ->join('payments', 'payments.unit_id', '=', 'units.id')
                          ->where('payments.status', 'completed');
                    }],
                    'payments.amount'
                )
                ->with('user:id,full_name')
                ->orderByDesc('revenue')
                ->take(5)
                ->get()
                ->map(fn ($l) => [
                    'name'    => $l->user->full_name ?? 'Unknown',
                    'revenue' => (float) ($l->revenue ?? 0),
                ])
                ->values()
                ->toArray();

            $revenueTrend = Payment::where('status', 'completed')
                ->where('payment_date', '>=', now()->subMonths(6)->startOfMonth())
                ->select(
                    DB::raw("DATE_FORMAT(payment_date, '%Y-%m') as month"),
                    DB::raw('SUM(amount) as total')
                )
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->map(fn ($r) => ['month' => $r->month, 'total' => (float) $r->total])
                ->values()
                ->toArray();

            $recentUsers = User::latest()->take(5)->get(['id', 'full_name', 'role', 'created_at'])
                ->map(fn ($u) => [
                    'id'         => $u->id,
                    'full_name'  => $u->full_name,
                    'role'       => $u->role,
                    'created_at' => $u->created_at?->toISOString(),
                ])
                ->values()
                ->toArray();

            $recentPayments = Payment::with('tenant.user:id,full_name')->latest()->take(5)->get()
                ->map(fn ($p) => [
                    'id'     => $p->id,
                    'amount' => (float) $p->amount,
                    'status' => $p->status,
                    'tenant' => $p->tenant && $p->tenant->user
                        ? ['user' => ['full_name' => $p->tenant->user->full_name]]
                        : null,
                ])
                ->values()
                ->toArray();

            return [
                'total_landlords'     => Landlord::count(),
                'total_tenants'       => Tenant::where('is_active', true)->count(),
                'total_properties'    => Property::count(),
                'total_units'         => Unit::count(),
                'total_revenue'       => (float) Payment::where('status', 'completed')->sum('amount'),
                'pending_maintenance' => MaintenanceRequest::whereNotIn('status', ['resolved', 'rejected'])->count(),
                'active_users'        => User::where('is_active', true)->count(),
                'charts' => [
                    'property_distribution'    => $propertyDistribution,
                    'top_landlords_by_revenue' => $topLandlords,
                    'revenue_trend'             => $revenueTrend,
                ],
                'recent_registrations' => $recentUsers,
                'recent_payments'      => $recentPayments,
            ];
        });

        return response()->json($data);
    }

    /** GET /api/landlord/dashboard — KPIs + chart data, scoped to the landlord. */
    public function landlord(Request $request): JsonResponse
    {
        $id = $request->user()->id;

        $data = Cache::remember("landlord_dashboard_{$id}", 300, function () use ($id) {
            $propertyIds = Property::where('landlord_id', $id)->pluck('id');
            $unitIds     = Unit::whereIn('property_id', $propertyIds)->pluck('id');

            $totalUnits    = $unitIds->count();
            $occupiedUnits = Unit::whereIn('id', $unitIds)->where('status', 'occupied')->count();

            $occupancyByProperty = Property::where('landlord_id', $id)
                ->withCount([
                    'units',
                    'units as occupied_count' => fn ($q) => $q->where('status', 'occupied'),
                ])
                ->get(['id', 'name'])
                ->map(fn ($p) => [
                    'name'     => $p->name,
                    'total'    => $p->units_count,
                    'occupied' => $p->occupied_count,
                ])
                ->values()
                ->toArray();

            return [
                'total_properties' => $propertyIds->count(),
                'total_units'      => $totalUnits,
                'occupied_units'   => $occupiedUnits,
                'vacant_units'     => $totalUnits - $occupiedUnits,
                'occupancy_rate'   => $totalUnits ? round($occupiedUnits / $totalUnits * 100, 1) : 0,
                'total_tenants'    => Tenant::where('landlord_id', $id)->where('is_active', true)->count(),
                'pending_payments' => Payment::whereIn('unit_id', $unitIds)->where('status', 'pending')->count(),
                'open_maintenance' => MaintenanceRequest::whereIn('property_id', $propertyIds)
                                        ->whereNotIn('status', ['resolved', 'rejected'])->count(),
                'charts' => [
                    'occupancy_by_property' => $occupancyByProperty,
                    'revenue_last_6_months' => $this->revenueLast6Months($unitIds),
                ],
            ];
        });

        return response()->json($data);
    }

    /** GET /api/caretaker/dashboard — KPIs for the authenticated caretaker. */
    public function caretaker(Request $request): JsonResponse
    {
        $id = $request->user()->id;

        return response()->json([
            'assigned_tasks'   => Task::where('assigned_to', $id)->count(),
            'in_progress'      => Task::where('assigned_to', $id)->where('status', 'in_progress')->count(),
            'awaiting_confirm' => Task::where('assigned_to', $id)
                                    ->where('is_completed_by_caretaker', true)
                                    ->where('tenant_confirmed', false)->count(),
            'completed'        => Task::where('assigned_to', $id)->where('status', 'completed')->count(),
            'open_requests'    => MaintenanceRequest::where('assigned_to', $id)
                                    ->whereNotIn('status', ['resolved', 'rejected'])->count(),
            'recent_tasks'     => Task::where('assigned_to', $id)
                                    ->with('maintenanceRequest:id,subject,priority')
                                    ->latest()->take(5)->get(),
        ]);
    }

    /** GET /api/tenant/dashboard — "My Unit" snapshot + payment/maintenance summary. */
    public function tenant(Request $request): JsonResponse
    {
        $id = $request->user()->id;

        $tenant = Tenant::with('activeOccupancy.unit.property:id,name')->find($id);
        $occupancy = $tenant?->activeOccupancy;

        return response()->json([
            'has_unit' => $occupancy !== null,
            'unit'     => $occupancy !== null ? [
                'unit_number' => $occupancy->unit->unit_number,
                'property'    => $occupancy->unit->property->name,
                'rent'        => $occupancy->rent_amount_at_start,
                'since'       => $occupancy->start_date,
            ] : null,
            'pending_payment' => Payment::where('tenant_id', $id)
                                    ->where('status', 'pending')->latest('due_date')->first(),
            'open_maintenance' => MaintenanceRequest::where('tenant_id', $id)
                                    ->whereNotIn('status', ['resolved', 'rejected'])->count(),
            'recent_payments' => Payment::where('tenant_id', $id)
                                    ->latest('due_date')->take(5)->get(),
        ]);
    }

    private function revenueLast6Months($unitIds): array
    {
        return Payment::whereIn('unit_id', $unitIds)
            ->where('status', 'completed')
            ->where('payment_date', '>=', now()->subMonths(6)->startOfMonth())
            ->select(
                DB::raw("DATE_FORMAT(payment_date, '%Y-%m') as month"),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn ($r) => ['month' => $r->month, 'total' => (float) $r->total])
            ->toArray();
    }
}
