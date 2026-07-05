<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    protected $fillable = [
        'maintenance_request_id',
        'assigned_to',
        'assigned_by',
        'task_description',
        'priority',
        'due_date',
        'status',
        'started_at',
        'completed_at',
        'completion_notes',
        'completion_photo',
        'tenant_confirmed',
        'is_completed_by_caretaker',
    ];

    protected function casts(): array
    {
        return [
            'due_date'                  => 'date',
            'started_at'                => 'datetime',
            'completed_at'              => 'datetime',
            'tenant_confirmed'          => 'boolean',
            'is_completed_by_caretaker' => 'boolean',
        ];
    }

    public function maintenanceRequest(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }

    public function caretaker(): BelongsTo
    {
        return $this->belongsTo(Caretaker::class, 'assigned_to', 'user_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}