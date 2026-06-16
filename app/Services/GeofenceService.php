<?php

namespace App\Services;

use App\Events\GeofenceEntered;
use App\Events\GeofenceExited;
use App\Models\DeviceSnapshot;
use App\Models\Geofence;

class GeofenceService
{
    public function contains(Geofence $geofence, float $latitude, float $longitude): bool
    {
        if ($geofence->type === 'circle') {
            $distance = $this->distanceMeters((float) $geofence->center_latitude, (float) $geofence->center_longitude, $latitude, $longitude);

            return $distance <= (int) $geofence->radius_meters;
        }

        $polygon = $geofence->polygon ?: [];
        $inside = false;
        $count = count($polygon);

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $xi = (float) $polygon[$i]['lat'];
            $yi = (float) $polygon[$i]['lng'];
            $xj = (float) $polygon[$j]['lat'];
            $yj = (float) $polygon[$j]['lng'];
            $intersect = (($yi > $longitude) !== ($yj > $longitude)) && ($latitude < ($xj - $xi) * ($longitude - $yi) / (($yj - $yi) ?: 0.000001) + $xi);
            if ($intersect) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    public function handle(array $payload = []): array
    {
        $snapshot = DeviceSnapshot::findOrFail($payload['snapshot_id']);
        $matches = Geofence::where('is_active', true)->get()->filter(fn (Geofence $geofence) => $this->contains($geofence, (float) $snapshot->latitude, (float) $snapshot->longitude));

        foreach ($matches as $geofence) {
            event(new GeofenceEntered(['geofence_id' => $geofence->id, 'snapshot_id' => $snapshot->id]));
        }

        return ['ok' => true, 'matches' => $matches->pluck('id')->values()->all()];
    }

    public function distanceMeters(float $fromLat, float $fromLng, float $toLat, float $toLng): float
    {
        $earth = 6371000;
        $latDelta = deg2rad($toLat - $fromLat);
        $lngDelta = deg2rad($toLng - $fromLng);
        $a = sin($latDelta / 2) ** 2 + cos(deg2rad($fromLat)) * cos(deg2rad($toLat)) * sin($lngDelta / 2) ** 2;

        return $earth * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
