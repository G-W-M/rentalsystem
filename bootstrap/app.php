<?php

use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

/**
 * MERGE-SAFETY: routing is wired ONCE here and then frozen.
 * Nobody edits this file after initial setup — role-scoped route additions
 * happen inside each dev's own file under routes/*.php.
 *
 * Ownership of the files required below:
 *   routes/auth.php       -> Dev B
 *   routes/landlord.php   -> Dev A
 *   routes/caretaker.php  -> Dev B
 *   routes/tenant.php     -> Dev B
 *   routes/web.php        -> frozen; only requires role web-route files (see note below)
 */
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // API routes, split per role, Sanctum-protected inside each file.
            \Illuminate\Support\Facades\Route::middleware('api')
                ->prefix('api')
                ->group(function () {
                    require base_path('routes/auth.php');
                    require base_path('routes/landlord.php');
                    require base_path('routes/caretaker.php');
                    require base_path('routes/tenant.php');
                    // require base_path('routes/admin.php'); // phase 2
                });
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);

        // Sanctum stateful API middleware (needed if the PWA calls the API
        // same-origin with cookies rather than bearer tokens).
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();