<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

/**
 * MERGE-SAFETY: Each role's routes live in a separate file so the two developers
 * never edit the same route file. Register your route file here ONCE (this block
 * is frozen after setup) and thereafter only ever touch YOUR OWN role file.
 *
 * Ownership:
 *   Dev B -> routes/auth.php, routes/caretaker.php, routes/tenant.php
 *   Dev A -> routes/landlord.php
 *   Shared/admin -> routes/admin.php (phase 2)
 */
class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/dashboard';

    public function boot(): void
    {
        $this->routes(function () {
            // ---- API routes (Sanctum-protected, per role) ----
            Route::middleware('api')
                ->prefix('api')
                ->group(function () {
                    require base_path('routes/auth.php');       // login/logout/me  (Dev B)
                    require base_path('routes/landlord.php');    // (Dev A)
                    require base_path('routes/caretaker.php');   // (Dev B)
                    require base_path('routes/tenant.php');      // (Dev B)
                    // require base_path('routes/admin.php');    // phase 2
                });

            // ---- Web (Blade) routes ----
            Route::middleware('web')
                ->group(base_path('routes/web.php'));            // frozen: only loads role web-routes
        });
    }
}