<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * NOTE ON OWNERSHIP: this is the SAME physical file that Developer B owns for
 * the caretaker()/tenant() methods. When the two developers' work is merged,
 * this file must contain ALL THREE methods (landlord + caretaker + tenant).
 * This copy holds Developer A's landlord() method; merge it into the shared
 * DashboardController rather than overwriting Dev B's methods.
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
