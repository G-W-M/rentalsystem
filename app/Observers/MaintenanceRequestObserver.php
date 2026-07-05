<?php

namespace App\Observers;

use App\Models\MaintenanceRequest;
use App\Models\Task;

class MaintenanceRequestObserver
{
    /**
     * Defensive backstop for auto-task generation. If a maintenance request
     * reaches the 'assigned' state with a caretaker but has no Task (e.g. its
     * status was changed outside MaintenanceController::approve), create one so
     * the workflow never strands a request without a task.
     */
    public function updated(MaintenanceRequest $request): void
    {
        $becameAssigned = $request->status === 'assigned' && $request->assigned_to !== null;

        if (! $becameAssigned) {
            return;
        }

        $hasTask = Task::where('maintenance_request_id', $request->id)->exists();

        if ($hasTask) {
            return;
        }

        Task::create([
            'maintenance_request_id' => $request->id,
            'assigned_to'            => $request->assigned_to,
            'assigned_by'            => $request->assigned_to,
            'task_description'       => $request->subject ?? $request->description,
            'priority'               => $request->priority,
            'status'                 => 'assigned',
        ]);
    }
}
