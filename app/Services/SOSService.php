<?php

namespace App\Services;

use App\Events\SOSTriggered;
use App\Models\SosEvent;

class SOSService
{
    public function trigger(array $payload): SosEvent
    {
        $event = SosEvent::create([
            'user_id' => $payload['user_id'],
            'device_id' => $payload['device_id'] ?? null,
            'tracking_group_id' => $payload['tracking_group_id'] ?? null,
            'latitude' => $payload['latitude'] ?? null,
            'longitude' => $payload['longitude'] ?? null,
            'status' => 'open',
            'message' => $payload['message'] ?? null,
            'meta' => $payload,
        ]);

        event(new SOSTriggered(['sos_event_id' => $event->id]));

        return $event;
    }

    public function resolve(SosEvent $event, int $userId): SosEvent
    {
        $event->forceFill(['status' => 'resolved', 'resolved_by' => $userId, 'resolved_at' => now()])->save();

        return $event;
    }

    public function handle(array $payload = []): array
    {
        return ['ok' => true, 'id' => $this->trigger($payload)->id];
    }
}
