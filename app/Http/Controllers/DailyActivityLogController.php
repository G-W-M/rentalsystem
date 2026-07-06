<?php

namespace App\Http\Controllers;

use App\Models\Caretaker;
use App\Models\DailyActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Caretaker daily activity log — end-of-day report submitted once per day.
 * Caretaker submits/views their own; landlord views their caretakers'.
 */
class DailyActivityLogController extends Controller
{
    /**
     * GET /api/caretaker/activity-logs
     * The authenticated caretaker's own submitted daily logs, newest first.
     */
    public function index(Request $request): JsonResponse
    {
        $logs = DailyActivityLog::where('caretaker_id', $request->user()->id)
            ->orderByDesc('log_date')
            ->paginate(30);

        return response()->json($logs);
    }

    /**
     * POST /api/caretaker/activity-logs
     * Submit today's activity log. One per caretaker per calendar day —
     * the DB has a unique index on (caretaker_id, log_date); we also check
     * here first so we can return a clean validation message instead of a
     * raw SQL integrity error.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'log_date'             => ['required', 'date'],
            'activities_performed' => ['required', 'string'],
            'notes'                => ['nullable', 'string'],
        ]);

        $exists = DailyActivityLog::where('caretaker_id', $request->user()->id)
            ->whereDate('log_date', $data['log_date'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'You have already submitted a log for this date.',
            ], 422);
        }

        $log = DailyActivityLog::create([
            'caretaker_id'          => $request->user()->id,
            'log_date'              => $data['log_date'],
            'activities_performed'  => $data['activities_performed'],
            'notes'                 => $data['notes'] ?? null,
            'submitted_at'          => now(),
        ]);

        return response()->json($log, 201);
    }

    /**
     * GET /api/landlord/activity-logs
     * Daily logs from all caretakers belonging to this landlord. Supports
     * an optional caretaker_id filter.
     */
    public function landlordIndex(Request $request): JsonResponse
    {
        $caretakerIds = Caretaker::where('landlord_id', $request->user()->id)->pluck('user_id');

        $query = DailyActivityLog::whereIn('caretaker_id', $caretakerIds)
            ->with('caretaker.user:id,full_name');

        if ($request->filled('caretaker_id')) {
            $query->where('caretaker_id', $request->input('caretaker_id'));
        }

        return response()->json($query->orderByDesc('log_date')->paginate(30));
    }
}
