<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ActionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $eventType, public string $title, public string $message, public array $payload = []) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['event_type' => $this->eventType, 'title' => $this->title, 'message' => $this->message, 'payload' => $this->payload];
    }
}
