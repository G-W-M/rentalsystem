<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OfflineSync
{
    /**
     * Supports the PWA offline queue. When the client replays a queued write it
     * sends an 'X-Offline-Replay' header with a client-generated idempotency key
     * in 'X-Idempotency-Key'. This middleware:
     *   - tags the request so controllers can treat replays idempotently
     *   - echoes the idempotency key back on the response for client reconciliation
     * Alias 'offline.sync' is registered in bootstrap/app.php.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isReplay       = $request->hasHeader('X-Offline-Replay');
        $idempotencyKey = $request->header('X-Idempotency-Key');

        $request->attributes->set('is_offline_replay', $isReplay);
        $request->attributes->set('idempotency_key', $idempotencyKey);

        $response = $next($request);

        if ($idempotencyKey !== null) {
            $response->headers->set('X-Idempotency-Key', $idempotencyKey);
        }

        return $response;
    }
}
