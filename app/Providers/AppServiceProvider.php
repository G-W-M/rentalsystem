<?php

namespace App\Providers;

use App\Models\MaintenanceRequest;
use App\Observers\MaintenanceRequestObserver;
use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    /**
     * OWNED BY DEVELOPER B. Register model observers and other boot-time
     * bindings here. Developer A requests additions via message rather than
     * editing this file directly, to keep it merge-safe.
     */
    public function boot(): void
    {
        MaintenanceRequest::observe(MaintenanceRequestObserver::class);

        $this->configureDateSerialization();
    }

    /**
     * Global date formatting for every API response.
     *
     * Deliberately done here rather than in each model's casts(). Casting
     * an attribute to 'date:d M Y' turns it into a formatted STRING, which
     * silently breaks any date maths elsewhere in the codebase — e.g.
     * Payment::isOverdue() calls $this->due_date->isPast(), which only
     * works while due_date is a real Carbon instance.
     *
     * Overriding Carbon's JSON serialiser instead keeps every date a Carbon
     * object in PHP (isPast(), comparisons, diffing all still work) while
     * rendering it human-readably the moment it hits a JSON response — so
     * no model casts and no frontend JS need to change.
     *
     *   due_date, start_date (midnight) -> "12 Jul 2026"
     *   verified_at, submitted_at, etc. -> "12 Jul 2026, 14:30"
     *
     * Pure dates are stored at midnight, so the ", 00:00" is stripped from
     * them rather than cluttering every date in the UI.
     */
    private function configureDateSerialization(): void
    {
        Carbon::serializeUsing(function (Carbon $date) {
            return $date->format('H:i:s') === '00:00:00'
                ? $date->format('d M Y')
                : $date->format('d M Y, H:i');
        });
    }
}