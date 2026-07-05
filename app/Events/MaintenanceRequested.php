<?php

namespace App\Events;

use App\Models\MaintenanceRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MaintenanceRequested
{
    use Dispatchable, SerializesModels;

    public function __construct(public MaintenanceRequest $maintenanceRequest)
    {
    }
}