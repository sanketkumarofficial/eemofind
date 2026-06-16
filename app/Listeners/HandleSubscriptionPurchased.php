<?php

namespace App\Listeners;

use App\Services\ActivityLogService;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleSubscriptionPurchased implements ShouldQueue
{
    public function __construct(private ActivityLogService $activityLogService)
    {
    }

    public function handle(object $event): void
    {
        $this->activityLogService->handle([
            'event' => 'SubscriptionPurchased',
            'payload' => property_exists($event, 'payload') ? $event->payload : [],
        ]);
    }
}