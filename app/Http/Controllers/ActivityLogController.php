<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin session/activity viewer (distinct from Caretaker daily activity
 * logs, which live in DailyActivityLogController). Surfaces three existing
 * tables for admin oversight: sessions, activity_logs, audit_trails.
 */
class ActivityLogController extends Controller
{
    /**
     * GET /api/admin/sessions
     * Filters: user_id, device_type, is_active (1/0).
     */
    public function sessions(Request $request): JsonResponse
    {
        $query = DB::table('sessions')
            ->join('users', 'users.id', '=', 'sessions.user_id')
            ->select('sessions.*', 'users.full_name', 'users.email', 'users.role');

        if ($request->filled('user_id')) {
            $query->where('sessions.user_id', $request->input('user_id'));
        }
        if ($request->filled('device_type')) {
            $query->where('sessions.device_type', $request->input('device_type'));
        }
        if ($request->filled('is_active')) {
            $query->where('sessions.is_active', $request->boolean('is_active'));
        }

        return response()->json($query->orderByDesc('sessions.login_time')->paginate(25));
    }

    /**
     * GET /api/admin/audit-trails
     * Filters: user_id, date_from, date_to.
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
     * The RICH per-activity log (activity_logs table). Admin-wide,
     * unscoped. Filters: caretaker_id, property_id, status.
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