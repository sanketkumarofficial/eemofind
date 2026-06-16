<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ActivityService
{
    public function log(string $module, string $action, string $description, ?Model $subject = null, array $properties = [], ?Request $request = null): ActivityLog
    {
        $request ??= request();
        $agent = strtolower((string) $request->userAgent());

        return ActivityLog::create([
            'user_id' => auth()->id(), 'module' => $module, 'action' => $action, 'description' => $description,
            'subject_type' => $subject?->getMorphClass(), 'subject_id' => $subject?->getKey(),
            'ip_address' => $request->ip(), 'browser' => $this->browser($agent), 'platform' => $this->platform($agent), 'properties' => $properties,
        ]);
    }

    private function browser(string $agent): string
    {
        return match (true) {
            str_contains($agent, 'edg') => 'Edge', str_contains($agent, 'chrome') => 'Chrome', str_contains($agent, 'firefox') => 'Firefox', str_contains($agent, 'safari') => 'Safari', default => 'Unknown'
        };
    }

    private function platform(string $agent): string
    {
        return match (true) {
            str_contains($agent, 'android') => 'Android', str_contains($agent, 'iphone') || str_contains($agent, 'ipad') => 'iOS', str_contains($agent, 'windows') => 'Windows', str_contains($agent, 'mac') => 'macOS', str_contains($agent, 'linux') => 'Linux', default => 'Unknown'
        };
    }
}
