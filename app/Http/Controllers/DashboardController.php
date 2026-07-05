<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Task;
use App\Models\Tenant;
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
    /**
     * GET /api/caretaker/dashboard
     * KPI snapshot for the authenticated caretaker, scoped to their tasks and
     * assigned maintenance requests.
     */
    public function caretaker(Request $request): JsonResponse
    {
        $id = $request->user()->id;

        return response()->json([
            'assigned_tasks'   => Task::where('assigned_to', $id)->count(),
            'in_progress'      => Task::where('assigned_to', $id)
                                    ->where('status', 'in_progress')->count(),
            'awaiting_confirm' => Task::where('assigned_to', $id)
                                    ->where('is_completed_by_caretaker', true)
                                    ->where('tenant_confirmed', false)->count(),
            'completed'        => Task::where('assigned_to', $id)
                                    ->where('status', 'completed')->count(),
            'open_requests'    => MaintenanceRequest::where('assigned_to', $id)
                                    ->whereNotIn('status', ['resolved', 'rejected'])->count(),
            'recent_tasks'     => Task::where('assigned_to', $id)
                                    ->with('maintenanceRequest:id,subject,priority')
                                    ->latest()->take(5)->get(),
        ]);
    }

    /**
     * GET /api/tenant/dashboard
     * "My Unit" snapshot plus payment and maintenance summary for the tenant.
     * Handles the no-active-unit empty state without erroring.
     */
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
                                    ->where('status', 'pending')
                                    ->latest('due_date')->first(),
            'open_maintenance' => MaintenanceRequest::where('tenant_id', $id)
                                    ->whereNotIn('status', ['resolved', 'rejected'])->count(),
            'recent_payments' => Payment::where('tenant_id', $id)
                                    ->latest('due_date')->take(5)->get(),
        ]);
    }
}
