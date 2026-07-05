<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use SoftDeletes;

public const STATUS_AVAILABLE = 'available';
    public const STATUS_OCCUPIED = 'occupied';
    public const STATUS_MAINTENANCE = 'maintenance';
    public const STATUS_UNAVAILABLE = 'unavailable';
    protected $fillable = [
        'property_id',
        'unit_number',
        'rent_amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'rent_amount' => 'decimal:2',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function occupancies(): HasMany
    {
        return $this->hasMany(TenantOccupancy::class);
    }

    public function activeOccupancy(): HasOne
    {
        return $this->hasOne(TenantOccupancy::class)->where('is_current', true);
    }
}
