<?php

namespace App\Services;

use App\Models\Device;

class HeartbeatService
{
    public function __construct(private TrackingService $trackingService, private FirebaseService $firebaseService)
    {
    }

    public function handle(array $payload = []): array
    {
        $device = Device::where('imei', $payload['imei'])->firstOrFail();
        $snapshot = $this->trackingService->ingestHeartbeat($device, $payload);
        $this->firebaseService->updateLiveLocation($device->imei, [
            'latitude' => $snapshot->latitude,
            'longitude' => $snapshot->longitude,
            'speed' => $snapshot->speed,
            'battery' => $snapshot->battery,
            'recorded_at' => $snapshot->recorded_at?->toISOString(),
        ]);

        return ['ok' => true, 'snapshot_id' => $snapshot->id];
    }
}
