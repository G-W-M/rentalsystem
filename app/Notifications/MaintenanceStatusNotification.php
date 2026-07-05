<?php

namespace App\Notifications;

use App\Models\MaintenanceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MaintenanceStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        public MaintenanceRequest $maintenanceRequest,
        public string $title,
        public string $body
    ) {
    }

    /**
     * Deliver via the database channel only (in-app notifications this phase;
     * no mail/broadcast). Laravel writes to its own notifications storage on the
     * notifiable; the app-level Notifications model is written separately by the
     * controllers/listeners for the custom in-app feed.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'maintenance_request_id' => $this->maintenanceRequest->id,
            'title'                  => $this->title,
            'message'                => $this->body,
            'status'                 => $this->maintenanceRequest->status,
            'type'                   => 'maintenance',
        ];
    }
}