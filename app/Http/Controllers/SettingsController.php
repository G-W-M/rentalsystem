<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * System-wide settings (the `settings` table already seeded with company
 * info). Only the theme setting is exposed via API for now — the rest
 * (company_name, rent_due_day, etc.) can be added the same way later.
 */
class SettingsController extends Controller
{
    /**
     * GET /api/admin/settings/theme
     * Anyone authenticated can READ the system theme (so it applies for
     * every role's UI), but only admin can change it (see updateTheme).
     */
    public function getTheme(): JsonResponse
    {
        $theme = DB::table('settings')->where('setting_key', 'system_theme')->value('setting_value');

        return response()->json(['theme' => $theme ?? 'light']);
    }

    /**
     * PUT /api/admin/settings/theme
     * Admin-only (route is gated by role:admin middleware).
     */
    public function updateTheme(Request $request): JsonResponse
    {
        $data = $request->validate([
            'theme' => ['required', 'in:light,dark'],
        ]);

        DB::table('settings')->updateOrInsert(
            ['setting_key' => 'system_theme'],
            [
                'setting_value' => $data['theme'],
                'setting_group' => 'appearance',
                'setting_type'  => 'string',
                'is_public'     => true,
                'description'   => 'Global light/dark mode for the whole system.',
                'updated_by'    => $request->user()->id,
                'updated_at'    => now(),
                'created_at'    => now(),
            ]
        );

        return response()->json(['message' => 'Theme updated.', 'theme' => $data['theme']]);
    }
}