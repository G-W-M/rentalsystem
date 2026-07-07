<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin session/activity viewer. Three distinct views:
 *   - sessions()          -> login/logout history with explicit timestamps + duration
 *   - auditTrails()       -> write-action audit trail
 *   - caretakerActivity() -> the rich per-activity log (activity_logs table)
 */
class ActivityLogController extends Controller
{
    /**
     * GET /api/admin/sessions
     * Filters: user_id, device_type, is_active (1/0).
     * Returns explicit login_time, logout_time, and a computed duration in
     * minutes (null if the session is still active / has no logout_time).
     */
    public function sessions(Request $request): JsonResponse
    {
        $query = DB::table('sessions')
            ->join('users', 'users.id', '=', 'sessions.user_id')
            ->select(
                'sessions.id',
                'sessions.user_id',
                'sessions.device_type',
                'sessions.ip_address',
                'sessions.login_time',
                'sessions.logout_time',
                'sessions.is_active',
                'users.full_name',
                'users.email',
                'users.role'
            );

        if ($request->filled('user_id')) {
            $query->where('sessions.user_id', $request->input('user_id'));
        }
        if ($request->filled('device_type')) {
            $query->where('sessions.device_type', $request->input('device_type'));
        }
        if ($request->filled('is_active')) {
            $query->where('sessions.is_active', $request->boolean('is_active'));
        }

        $sessions = $query->orderByDesc('sessions.login_time')->paginate(25);

        // Compute duration in minutes for each row (done in PHP rather than
        // SQL so it works identically across MySQL/SQLite during testing).
        $sessions->getCollection()->transform(function ($s) {
            $s->duration_minutes = null;
            if ($s->login_time && $s->logout_time) {
                $login = \Carbon\Carbon::parse($s->login_time);
                $logout = \Carbon\Carbon::parse($s->logout_time);
                $s->duration_minutes = $logout->diffInMinutes($login);
            }
            return $s;
        });

        return response()->json($sessions);
    }

    /**
     * GET /api/admin/audit-trails
     */
    public function auditTrails(Request $request): JsonResponse
    {
        $query = DB::table('audit_trails')
            ->leftJoin('users', 'users.id', '=', 'audit_trails.user_id')
            ->select('audit_trails.*', 'users.full_name', 'users.email');

        if ($request->filled('user_id')) {
            $query->where('audit_trails.user_id', $request->input('user_id'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('audit_trails.created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('audit_trails.created_at', '<=', $request->input('date_to'));
        }

        return response()->json($query->orderByDesc('audit_trails.created_at')->paginate(25));
    }

    /**
     * GET /api/admin/caretaker-activity
     */
    public function caretakerActivity(Request $request): JsonResponse
    {
        $query = DB::table('activity_logs')
            ->join('users', 'users.id', '=', 'activity_logs.caretaker_id')
            ->leftJoin('properties', 'properties.id', '=', 'activity_logs.property_id')
            ->select(
                'activity_logs.*',
                'users.full_name as caretaker_name',
                'properties.name as property_name'
            );

        if ($request->filled('caretaker_id')) {
            $query->where('activity_logs.caretaker_id', $request->input('caretaker_id'));
        }
        if ($request->filled('property_id')) {
            $query->where('activity_logs.property_id', $request->input('property_id'));
        }
        if ($request->filled('status')) {
            $query->where('activity_logs.status', $request->input('status'));
        }

        return response()->json($query->orderByDesc('activity_logs.log_date')->paginate(25));
    }
}