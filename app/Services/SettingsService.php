<?php

namespace App\Services;

use App\Models\Setting;

class SettingsService
{
    public function get(string $group, string $key, mixed $default = null): mixed
    {
        return Setting::where(compact('group', 'key'))->value('value') ?? $default;
    }

    public function set(string $group, string $key, mixed $value): Setting
    {
        return Setting::updateOrCreate(compact('group', 'key'), ['value' => $value]);
    }

    public function handle(array $payload = []): array
    {
        $setting = $this->set($payload['group'], $payload['key'], $payload['value'] ?? null);

        return ['ok' => true, 'id' => $setting->id];
    }
}
