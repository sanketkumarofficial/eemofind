<?php

namespace App\Listeners;

use App\Events\DomainAction;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendDomainActionNotification implements ShouldQueue
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function handle(DomainAction $event): void
    {
        $this->notifications->send($event->recipient, $event->type, $event->title, $event->message, $event->payload);
    }
}
