<?php

namespace App\Services;

use App\Models\NotificationLog;
use App\Models\User;
use App\Notifications\ActionNotification;

class NotificationService
{
    public function send(User $user, string $eventType, string $title, string $message, array $payload = []): void
    {
        $user->notify(new ActionNotification($eventType, $title, $message, $payload));
        NotificationLog::create(['user_id' => $user->id, 'channel' => 'database', 'event_type' => $eventType, 'title' => $title, 'message' => $message, 'payload' => $payload, 'status' => 'sent', 'sent_at' => now()]);
    }
}
