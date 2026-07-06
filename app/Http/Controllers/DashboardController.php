<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * SHARED FILE — owned jointly by Developer A (landlord) and Developer B
 * (caretaker, tenant). All three methods must always live in this one file.
 * Never replace this file with a version containing only one developer's
 * method; always append/merge instead.
 */
class DashboardController extends Controller
{
    /** GET /api/landlord/dashboard — KPIs + chart data, scoped to the landlord. */
    public function landlord(Request $request): JsonResponse
    {
        $id = $request->user()->id;

        $data = Cache::remember("landlord_dashboard_{$id}", 300, function () use ($id) {
            $propertyIds = Property::where('landlord_id', $id)->pluck('id');
            $unitIds     = Unit::whereIn('property_id', $propertyIds)->pluck('id');

            $totalUnits    = $unitIds->count();
            $occupiedUnits = Unit::whereIn('id', $unitIds)->where('status', 'occupied')->count();

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
                    'occupancy_by_property' => Property::where('landlord_id', $id)
                        ->withCount([
                            'units',
                            'units as occupied_count' => fn ($q) => $q->where('status', 'occupied'),
                        ])
                        ->get(['id', 'name'])
                        ->map(fn ($p) => [
                            'name'     => $p->name,
                            'total'    => $p->units_count,
                            'occupied' => $p->occupied_count,
                        ]),
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
