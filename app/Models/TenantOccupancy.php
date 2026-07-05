<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantOccupancy extends Model
{
    protected $fillable = [
        'tenant_id',
        'unit_id',
        'start_date',
        'end_date',
        'is_current',
        'rent_amount_at_start',
        'lease_agreement_path',
        'deposit_paid',
        'deposit_amount',
        'deposit_refunded',
        'termination_reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date'           => 'date',
            'end_date'             => 'date',
            'is_current'           => 'boolean',
            'deposit_paid'         => 'boolean',
            'deposit_refunded'     => 'boolean',
            'rent_amount_at_start' => 'decimal:2',
            'deposit_amount'       => 'decimal:2',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
