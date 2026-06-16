<?php

return [
    'heartbeat_interval' => (int) env('EEMO_HEARTBEAT_INTERVAL', 5),
    'offline_timeout' => (int) env('EEMO_OFFLINE_TIMEOUT', 10),
    'pairing_code_length' => (int) env('EEMO_PAIRING_CODE_LENGTH', 8),
    'firebase' => [
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'database_url' => env('FIREBASE_DATABASE_URL'),
        'credentials' => env('FIREBASE_CREDENTIALS'),
    ],
    'razorpay' => [
        'key' => env('RAZORPAY_KEY'),
        'secret' => env('RAZORPAY_SECRET'),
        'currency' => env('RAZORPAY_CURRENCY', 'INR'),
    ],
];
