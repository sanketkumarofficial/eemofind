<?php

namespace App\Services;

use App\Models\NotificationLog;

class PushNotificationService
{
    public function sendToToken(string $token, string $title, string $body, array $payload = []): NotificationLog
    {
        return NotificationLog::create([
            'channel' => 'fcm',
            'target' => $token,
            'title' => $title,
            'body' => $body,
            'status' => 'queued',
            'payload' => $payload,
        ]);
    }

    public function handle(array $payload = []): array
    {
        $log = $this->sendToToken($payload['token'], $payload['title'], $payload['body'], $payload['payload'] ?? []);

        return ['ok' => true, 'notification_log_id' => $log->id];
    }
}
