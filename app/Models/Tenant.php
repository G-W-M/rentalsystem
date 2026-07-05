<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tenant extends Model
{
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'user_id',
        'landlord_id',
        'id_number',
        'nationality',
        'date_of_birth',
        'gender',
        'emergency_contact',
        'emergency_phone',
        'employment_status',
        'employer_name',
        'employer_phone',
        'is_active',
        'moved_in_date',
        'moved_out_date',
    ];

    protected function casts(): array
    {
        return [
            'is_active'      => 'boolean',
            'date_of_birth'  => 'date',
            'moved_in_date'  => 'date',
            'moved_out_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function landlord(): BelongsTo
    {
        return $this->belongsTo(Landlord::class, 'landlord_id', 'user_id');
    }

    public function occupancies(): HasMany
    {
        return $this->hasMany(TenantOccupancy::class, 'tenant_id', 'user_id');
    }

    public function activeOccupancy(): HasOne
    {
        return $this->hasOne(TenantOccupancy::class, 'tenant_id', 'user_id')->where('is_current', true);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'tenant_id', 'user_id');
    }
}
