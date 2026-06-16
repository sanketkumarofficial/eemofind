<?php

namespace App\Services;

use App\Models\Device;
use Illuminate\Support\Collection;

class RoutePlaybackService
{
    public function playback(Device $device, mixed $from, mixed $to): array
    {
        $points = $device->snapshots()
            ->whereBetween('recorded_at', [$from, $to])
            ->orderBy('recorded_at')
            ->get();

        return [
            'points' => $points,
            'distance_meters' => $this->distance($points),
            'stops' => $this->stops($points),
            'max_speed' => (float) $points->max('speed'),
            'average_speed' => round((float) $points->avg('speed'), 2),
        ];
    }

    public function handle(array $payload = []): array
    {
        $device = Device::findOrFail($payload['device_id']);

        return $this->playback($device, $payload['from'] ?? now()->subDay(), $payload['to'] ?? now());
    }

    private function distance(Collection $points): float
    {
        $geofence = new GeofenceService();
        $distance = 0.0;

        $points->values()->each(function ($point, $index) use ($points, $geofence, &$distance) {
            if ($index === 0) {
                return;
            }
            $previous = $points[$index - 1];
            $distance += $geofence->distanceMeters((float) $previous->latitude, (float) $previous->longitude, (float) $point->latitude, (float) $point->longitude);
        });

        return round($distance, 2);
    }

    private function stops(Collection $points): array
    {
        return $points->where('speed', '<=', 1)->values()->all();
    }
}
