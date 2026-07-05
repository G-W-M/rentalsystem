<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage on routes:  ->middleware('role:landlord,admin')
 * Register in bootstrap/app.php (Laravel 11) withMiddleware():
 *   $middleware->alias(['role' => \App\Http\Middleware\RoleMiddleware::class]);
 */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! in_array($user->role, $roles, true)) {
            return response()->json([
                'message' => 'Forbidden. This action requires one of: ' . implode(', ', $roles),
            ], 403);
        }

        return $next($request);
    }
}