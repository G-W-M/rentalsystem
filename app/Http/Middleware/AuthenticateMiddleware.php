<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMiddleware
{
    /**
     * Lightweight JSON-friendly authentication guard for API routes.
     * Ensures a Sanctum-authenticated, active user is present before proceeding.
     * Register alias 'auth.api' in bootstrap/app.php if used directly; most routes
     * use the built-in 'auth:sanctum' guard, this exists per the structure doc as an
     * explicit, JSON-first alternative that never redirects to a login page.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'Account inactive.'], 403);
        }

        return $next($request);
    }
}