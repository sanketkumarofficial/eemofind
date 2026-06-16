<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogService
{
    public function handle(array $payload = []): array
    {
        $log = ActivityLog::create([
            'user_id' => $payload['user_id'] ?? Auth::id(),
            'event' => $payload['event'] ?? 'system.event',
            'subject_type' => $payload['subject_type'] ?? null,
            'subject_id' => $payload['subject_id'] ?? $payload['id'] ?? null,
            'ip_address' => Request::ip(),
            'user_agent' => substr((string) Request::userAgent(), 0, 255),
            'properties' => $payload,
        ]);

        return ['ok' => true, 'id' => $log->id];
    }
}
