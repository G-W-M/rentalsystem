<?php

namespace App\Models;

use App\Models\Concerns\SerializesDatesReadably;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MaintenanceRequest extends Model
{
    use SerializesDatesReadably;

    public const CATEGORY_PLUMBING = 'plumbing';
    public const CATEGORY_ELECTRICAL = 'electrical';
    public const CATEGORY_STRUCTURAL = 'structural';
    public const CATEGORY_APPLIANCE = 'appliance';
    public const CATEGORY_PEST = 'pest';
    public const CATEGORY_SECURITY = 'security';
    public const CATEGORY_OTHER = 'other';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_EMERGENCY = 'emergency';

    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'tenant_id',
        'unit_id',
        'property_id',
        'category',
        'description',
        'subject',
        'priority',
        'status',
        'is_major',
        'cost_estimate',
        'actual_cost',
        'before_photo',
        'after_photo',
        'assigned_to',
        'submitted_at',
        'assigned_at',
        'resolved_at',
        'approved_by_landlord',
        'approved_at',
        'resolution_notes',
    ];

    protected function casts(): array
    {
        return [
            'is_major'             => 'boolean',
            'approved_by_landlord' => 'boolean',
            'submitted_at'         => 'datetime',
            'assigned_at'          => 'datetime',
            'resolved_at'          => 'datetime',
            'approved_at'          => 'datetime',
            'cost_estimate'        => 'decimal:2',
            'actual_cost'          => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'user_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function caretaker(): BelongsTo
    {
        return $this->belongsTo(Caretaker::class, 'assigned_to', 'user_id');
    }

    public function task(): HasOne
    {
        return $this->hasOne(Task::class);
    }
}
