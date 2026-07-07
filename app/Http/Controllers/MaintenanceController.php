<?php

namespace App\Http\Controllers;

use App\Events\MaintenanceRequested;
use App\Http\Requests\Landlord\ApproveMaintenanceRequest;
use App\Http\Requests\Tenant\MaintenanceRequestRequest;
use App\Models\Caretaker;
use App\Models\MaintenanceRequest;
use App\Models\Notifications;
use App\Models\Task;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaintenanceController extends Controller
{
    /**
     * GET /api/landlord/maintenance
     */
    public function landlordIndex(Request $request): JsonResponse
    {
        $query = MaintenanceRequest::whereHas('property', fn ($q) =>
                $q->where('landlord_id', $request->user()->id))
            ->with('tenant.user:id,full_name', 'unit:id,unit_number', 'property:id,name');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }
        if ($request->filled('property_id')) {
            $query->where('property_id', $request->input('property_id'));
        }

        return response()->json($query->latest()->paginate(20));
    }

    /**
     * GET /api/admin/maintenance
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $query = MaintenanceRequest::with('tenant.user:id,full_name', 'unit:id,unit_number', 'property:id,name');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        return response()->json($query->latest()->paginate(20));
    }

    /**
     * GET /api/tenant/maintenance
     */
    public function tenantIndex(Request $request): JsonResponse
    {
        $requests = MaintenanceRequest::where('tenant_id', $request->user()->id)
            ->with('task:id,maintenance_request_id,status,is_completed_by_caretaker,tenant_confirmed,completion_notes')
            ->latest()
            ->get();

        return response()->json($requests);
    }

    /**
     * POST /api/tenant/maintenance
     */
    public function store(MaintenanceRequestRequest $request): JsonResponse
    {
        $tenant = Tenant::with('activeOccupancy.unit')->find($request->user()->id);
        $occupancy = $tenant?->activeOccupancy;

        if ($occupancy === null) {
            return response()->json(['message' => 'You have no active unit to raise a request for.'], 422);
        }

        $recentOpen = MaintenanceRequest::where('unit_id', $occupancy->unit_id)
            ->where('tenant_id', $tenant->user_id)
            ->whereNotIn('status', ['resolved', 'rejected'])
            ->where('created_at', '>=', now()->subDays(7))
            ->exists();

        if ($recentOpen) {
            return response()->json(['message' => 'You already have a recent open request for this unit.'], 422);
        }

        $maintenance = MaintenanceRequest::create([
            'tenant_id'    => $tenant->user_id,
            'unit_id'      => $occupancy->unit_id,
            'property_id'  => $occupancy->unit->property_id,
            'category'     => $request->category,
            'subject'      => $request->subject,
            'description'  => $request->description,
            'priority'     => $request->input('priority', 'medium'),
            'status'       => 'submitted',
            'submitted_at' => now(),
        ]);

        event(new MaintenanceRequested($maintenance));

        return response()->json($maintenance, 201);
    }

    /**
     * POST /api/landlord/maintenance/{maintenance}/approve
     *
     * CHANGED: the caretaker assigned is now STRICTLY the one caretaker
     * assigned to the request's specific property_id (caretakers.property_id).
     * "Any active caretaker of the landlord" is no longer used — a request
     * only ever goes to the caretaker actually responsible for that
     * property. If that property has no assigned caretaker, approval is
     * blocked with a clear message telling the landlord to assign one.
     */
    public function approve(ApproveMaintenanceRequest $request, MaintenanceRequest $maintenance): JsonResponse
    {
        $owns = $maintenance->property()
            ->where('landlord_id', $request->user()->id)
            ->exists();

        abort_unless($owns, 403, 'Not your property.');

        if ($maintenance->status !== 'submitted') {
            return response()->json(['message' => 'Only submitted requests can be approved.'], 422);
        }

        $caretakerId = Caretaker::where('property_id', $maintenance->property_id)
            ->where('is_active', true)
            ->value('user_id');

        if ($caretakerId === null) {
            return response()->json([
                'message' => 'Cannot approve: no caretaker is assigned to this property yet. Assign one first.',
            ], 422);
        }

        DB::transaction(function () use ($maintenance, $caretakerId, $request) {
            $maintenance->update([
                'status'               => 'assigned',
                'approved_by_landlord' => true,
                'approved_at'          => now(),
                'assigned_to'          => $caretakerId,
                'assigned_at'          => now(),
            ]);

            Task::firstOrCreate(
                ['maintenance_request_id' => $maintenance->id],
                [
                    'assigned_to'      => $caretakerId,
                    'assigned_by'      => $request->user()->id,
                    'task_description' => $maintenance->subject ?? $maintenance->description,
                    'priority'         => $maintenance->priority,
                    'status'           => 'assigned',
                ]
            );

            Notifications::create([
                'user_id' => $caretakerId,
                'title'   => 'New maintenance task assigned',
                'message' => $maintenance->subject ?? 'A maintenance task was assigned to you.',
                'type'    => 'maintenance',
            ]);
        });

        return response()->json([
            'message' => 'Approved and task assigned.',
            'request' => $maintenance->fresh('task'),
        ]);
    }

    /**
     * POST /api/landlord/maintenance/{maintenance}/reject
     */
    public function reject(Request $request, MaintenanceRequest $maintenance): JsonResponse
    {
        $owns = $maintenance->property()
            ->where('landlord_id', $request->user()->id)
            ->exists();

        abort_unless($owns, 403, 'Not your property.');

        $request->validate([
            'resolution_notes' => ['required', 'string'],
        ]);

        $maintenance->update([
            'status'           => 'rejected',
            'resolution_notes' => $request->resolution_notes,
        ]);

        Notifications::create([
            'user_id' => $maintenance->tenant_id,
            'title'   => 'Maintenance request rejected',
            'message' => $request->resolution_notes,
            'type'    => 'maintenance',
        ]);

        return response()->json(['message' => 'Request rejected.']);
    }

    /**
     * POST /api/tenant/maintenance/{maintenance}/confirm
     */
    public function confirm(Request $request, MaintenanceRequest $maintenance): JsonResponse
    {
        abort_unless($maintenance->tenant_id === $request->user()->id, 403, 'Not your request.');

        $task = $maintenance->task;

        if ($task === null || ! $task->is_completed_by_caretaker) {
            return response()->json([
                'message' => 'Nothing to confirm yet — the caretaker has not completed the work.',
            ], 422);
        }

        DB::transaction(function () use ($maintenance, $task) {
            $task->update([
                'tenant_confirmed' => true,
                'status'           => 'completed',
            ]);

            $maintenance->update([
                'status'      => 'resolved',
                'resolved_at' => now(),
            ]);
        });

        return response()->json(['message' => 'Work confirmed. Request resolved.']);
    }
}