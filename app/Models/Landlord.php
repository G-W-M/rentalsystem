<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Landlord extends Model
{
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'user_id',
        'company_name',
        'id_number',
        'kra_pin',
        'physical_address',
        'is_verified',
        'verification_date',
        'max_properties',
        'registration_date',
    ];

    protected function casts(): array
    {
        return [
            'is_verified'       => 'boolean',
            'verification_date' => 'datetime',
            'registration_date' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'landlord_id', 'user_id');
    }

    public function caretakers(): HasMany
    {
        return $this->hasMany(Caretaker::class, 'landlord_id', 'user_id');
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'landlord_id', 'user_id');
    }
}
