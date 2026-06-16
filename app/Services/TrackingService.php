<?php

namespace App\Services;

use App\Models\Device;
use App\Models\DeviceSnapshot;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TrackingService
{
    public function __construct(private readonly FirebaseService $firebase) {}

    public function heartbeat(Device $device, array $payload): DeviceSnapshot
    {
        $recordedAt = Carbon::parse($payload['timestamp'] ?? now());
        $firebaseData = ['battery' => $payload['battery'], 'network' => $payload['network'], 'gps' => $payload['gps'], 'timestamp' => $recordedAt->toIso8601String()];
        $this->firebase->set("heartbeats/{$device->imei}", $firebaseData);
        $this->firebase->update("device_status/{$device->imei}", array_merge($firebaseData, ['online_status' => 'online', 'last_seen' => $recordedAt->toIso8601String()]));

        return DeviceSnapshot::updateOrCreate(['device_id' => $device->id], [
            'battery' => $payload['battery'], 'network' => $payload['network'], 'gps_status' => $payload['gps'],
            'is_online' => true, 'last_seen_at' => $recordedAt,
        ]);
    }

    public function location(Device $device, array $payload): DeviceSnapshot
    {
        $recordedAt = Carbon::parse($payload['timestamp'] ?? now());
        $data = array_merge($payload, ['timestamp' => $recordedAt->toIso8601String()]);
        $this->firebase->set("live_locations/{$device->user_id}", $data);
        $this->firebase->push("location_history/{$device->user_id}/{$recordedAt->toDateString()}", $data);

        return DeviceSnapshot::updateOrCreate(['device_id' => $device->id], [
            'latitude' => $payload['latitude'], 'longitude' => $payload['longitude'], 'speed' => $payload['speed'] ?? 0,
            'accuracy' => $payload['accuracy'] ?? null, 'heading' => $payload['heading'] ?? null, 'battery' => $payload['battery'] ?? null,
            'motion_status' => ($payload['speed'] ?? 0) > 1 ? 'moving' : 'idle', 'is_online' => true,
            'last_seen_at' => $recordedAt, 'location_recorded_at' => $recordedAt,
        ]);
    }

    public function markOfflineDevices(): int
    {
        $cutoff = now()->subMinutes((int) app(SettingService::class)->get('offline_timeout', config('eemo.offline_timeout')));
        $devices = Device::whereHas('snapshot', fn ($q) => $q->where('is_online', true)->where('last_seen_at', '<', $cutoff))->with('snapshot')->get();
        foreach ($devices as $device) {
            DB::transaction(fn () => $device->snapshot->update(['is_online' => false, 'motion_status' => 'offline']));
            $this->firebase->update("device_status/{$device->imei}", ['online_status' => 'offline']);
        }

        return $devices->count();
    }
}
