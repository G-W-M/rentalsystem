<?php

namespace App\Http\Controllers;

use App\Http\Requests\Caretaker\TaskRequest;
use App\Models\Notifications;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    /**
     * GET /api/caretaker/tasks
     * Tasks assigned to the authenticated caretaker.
     */
    public function index(Request $request): JsonResponse
    {
        $tasks = Task::where('assigned_to', $request->user()->id)
            ->with('maintenanceRequest:id,subject,category,priority,unit_id,property_id')
            ->latest()
            ->get();

        return response()->json($tasks);
    }

    /**
     * POST /api/caretaker/tasks/{task}/start
     * Moves an assigned task to in_progress and syncs the parent request.
     */
    public function start(Request $request, Task $task): JsonResponse
    {
        $this->ownTask($request, $task);

        if ($task->status !== 'assigned') {
            return response()->json(['message' => 'Only assigned tasks can be started.'], 422);
        }

        DB::transaction(function () use ($task) {
            $task->update([
                'status'     => 'in_progress',
                'started_at' => now(),
            ]);

            $task->maintenanceRequest?->update(['status' => 'in_progress']);
        });

        return response()->json(['message' => 'Task started.', 'task' => $task]);
    }

    /**
     * POST /api/caretaker/tasks/{task}/complete
     * Caretaker marks the work done but does NOT resolve the request. The
     * request stays open until the tenant confirms (double sign-off).
     */
    public function complete(TaskRequest $request, Task $task): JsonResponse
    {
        $this->ownTask($request, $task);

        if (! in_array($task->status, ['assigned', 'in_progress'], true)) {
            return response()->json(['message' => 'This task cannot be completed from its current state.'], 422);
        }

        DB::transaction(function () use ($task, $request) {
            $task->update([
                'is_completed_by_caretaker' => true,
                'completed_at'              => now(),
                'completion_notes'          => $request->completion_notes,
                'completion_photo'          => $request->completion_photo,
            ]);

            $tenantId = $task->maintenanceRequest?->tenant_id;

            if ($tenantId !== null) {
                Notifications::create([
                    'user_id' => $tenantId,
                    'title'   => 'Please confirm completed work',
                    'message' => 'Your caretaker marked the maintenance work complete. Please confirm.',
                    'type'    => 'maintenance',
                ]);
            }
        });

        return response()->json([
            'message' => 'Marked complete. Awaiting tenant confirmation.',
            'task'    => $task,
        ]);
    }

    private function ownTask(Request $request, Task $task): void
    {
        abort_unless($task->assigned_to === $request->user()->id, 403, 'Not your task.');
    }
}