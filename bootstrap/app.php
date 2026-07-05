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
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(__DIR__ . '/../routes/api.php');
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