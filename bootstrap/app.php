<?php

use App\Http\Middleware\AuthenticateMiddleware;
use App\Http\Middleware\LogActivityMiddleware;
use App\Http\Middleware\OfflineSync;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            // ---- Web routes: Dev B's file + Dev A's landlord partial ----
            Route::middleware('web')->group(function () {
                require __DIR__ . '/../routes/web.php';           // Dev B
                require __DIR__ . '/../routes/landlord_web.php';  // Dev A
            });

            // ---- API routes: Dev B's file + Dev A's landlord partial ----
            Route::middleware('api')
                ->prefix('api')
                ->group(function () {
                    require __DIR__ . '/../routes/api.php';           // Dev B
                    require __DIR__ . '/../routes/landlord_api.php';  // Dev A
                });
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role'          => RoleMiddleware::class,
            'auth.api'      => AuthenticateMiddleware::class,
            'activity'      => LogActivityMiddleware::class,
            'offline.sync'  => OfflineSync::class,
        ]);
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(function ($request) {
            return $request->is('api/*') || $request->expectsJson();
        });
    })
    ->create();
