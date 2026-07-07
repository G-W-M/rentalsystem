<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Caretaker extends Model
{
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'user_id',
        'landlord_id',
        'property_id',
        'id_number',
        'emergency_contact',
        'emergency_phone',
        'skills',
        'is_active',
        'hire_date',
        'termination_date',
        'rating',
        'salary',
    ];

    protected function casts(): array
    {
        return [
            'is_active'        => 'boolean',
            'skills'           => 'array',
            'hire_date'        => 'date',
            'termination_date' => 'date',
            'rating'           => 'decimal:1',
            'salary'           => 'decimal:2',
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

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to', 'user_id');
    }
}