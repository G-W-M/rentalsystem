<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The caretaker's own end-of-day report — one row per caretaker per day
 * (enforced by a unique DB index on caretaker_id+log_date). Distinct from
 * the richer per-activity `activity_logs` table (which has activity_type,
 * property_id, review workflow) — this one is a simple free-text daily
 * summary the caretaker submits once at end of day.
 */
class DailyActivityLog extends Model
{
    protected $table = 'daily_activity_logs';

    protected $fillable = [
        'caretaker_id',
        'log_date',
        'activities_performed',
        'notes',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'log_date'     => 'date',
            'submitted_at' => 'datetime',
        ];
    }

    public function caretaker(): BelongsTo
    {
        return $this->belongsTo(Caretaker::class, 'caretaker_id', 'user_id');
    }
}
