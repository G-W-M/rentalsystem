<?php

namespace App\Providers;

use App\Models\MaintenanceRequest;
use App\Observers\MaintenanceRequestObserver;
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
    }
}
