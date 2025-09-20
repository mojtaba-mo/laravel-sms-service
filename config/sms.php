<?php

return [
    'melipayamak' => [
        'username' => env('MELIPAYAMAK_USERNAME'),
        'password' => env('MELIPAYAMAK_PASSWORD'),
        'endpoint' => env('MELIPAYAMAK_ENDPOINT', 'http://api.payamak-panel.com/post/Send.asmx?wsdl')
    ],
    
    // OTP Settings
    'ttl_minutes' => env('OTP_TTL_MINUTES', 2),
    'daily_limit' => env('OTP_DAILY_LIMIT', 3),
    'min_interval_seconds' => env('OTP_MIN_INTERVAL_SECONDS', 120),
];
