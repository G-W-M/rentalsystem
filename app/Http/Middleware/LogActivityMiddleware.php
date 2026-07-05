<?php

namespace App\Http\Middleware;

use App\Models\Notifications;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class LogActivityMiddleware
{
    /**
     * Records an immutable audit row for state-changing authenticated requests
     * (POST/PUT/PATCH/DELETE). Writes to the audit_trails table if present.
     * Alias 'activity' is registered in bootstrap/app.php.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();

        $isWrite = in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true);

        if ($user !== null && $isWrite && Schema::hasTable('audit_trails')) {
            DB::table('audit_trails')->insert([
                'user_id'    => $user->id,
                'action'     => $request->method() . ' ' . $request->path(),
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $response;
    }
}