<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class NotificationService
{
    public static function send(
        int $userId,
        string $title,
        string $message,
        string $type = 'general',
        array $extra = []
    ) {
        DB::table('notifications')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => $type,
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => $userId,
            'data' => json_encode([
                'title' => $title,
                'message' => $message,
                'extra' => $extra
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}