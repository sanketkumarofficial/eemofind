<?php

namespace App\Listeners;

use App\Services\ActivityLogService;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleGeofenceEntered implements ShouldQueue
{
    public function __construct(private ActivityLogService $activityLogService)
    {
    }

    public function handle(object $event): void
    {
        $this->activityLogService->handle([
            'event' => 'GeofenceEntered',
            'payload' => property_exists($event, 'payload') ? $event->payload : [],
        ]);
    }
}