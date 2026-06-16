<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class SettingService
{
    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting.{$key}", 300, function () use ($key, $default) {
            $setting = Setting::where('key', $key)->first();

            return $setting ? $setting->decoded_value : $default;
        });
    }

    public function set(string $group, string $key, mixed $value, string $type = 'string', bool $encrypted = false): Setting
    {
        $encoded = $type === 'json' ? json_encode($value, JSON_THROW_ON_ERROR) : (string) $value;
        if ($encrypted && $encoded !== '') {
            $encoded = Crypt::encryptString($encoded);
        }
        $setting = Setting::updateOrCreate(['key' => $key], ['group' => $group, 'value' => $encoded, 'type' => $type, 'is_encrypted' => $encrypted]);
        Cache::forget("setting.{$key}");

        return $setting;
    }
}
