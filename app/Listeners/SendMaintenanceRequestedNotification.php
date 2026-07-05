<?php

namespace App\Listeners;

use App\Events\MaintenanceRequested;
use App\Models\Notifications;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SendMaintenanceRequestedNotification
{
    /**
     * When a tenant submits a maintenance request, notify the property's
     * landlord (via the landlords table) so they can approve it. Auto-discovered
     * by Laravel from the type-hinted event; no manual registration required.
     */
    public function handle(MaintenanceRequested $event): void
    {
        $request = $event->maintenanceRequest;

        if ($request->property_id === null || ! Schema::hasTable('properties')) {
            return;
        }

        $landlordId = DB::table('properties')
            ->where('id', $request->property_id)
            ->value('landlord_id');

        if ($landlordId === null) {
            return;
        }

        Notifications::create([
            'user_id' => $landlordId,
            'title'   => 'New maintenance request',
            'message' => $request->subject ?? 'A tenant submitted a new maintenance request.',
            'type'    => 'maintenance',
        ]);
    }
}