<?php

return [
    'face_match_threshold' => (float) env('HUMIFY_FACE_MATCH_THRESHOLD', 80),
    'company_timezone' => env('HUMIFY_COMPANY_TIMEZONE', 'Asia/Jakarta'),
    'close_day_time' => env('HUMIFY_CLOSE_DAY_TIME', '01:00'),
    'demo_password' => env('HUMIFY_DEMO_PASSWORD', 'Password123!'),
    'token_expiration_minutes' => env('HUMIFY_TOKEN_EXPIRATION_MINUTES') !== null
        ? (int) env('HUMIFY_TOKEN_EXPIRATION_MINUTES')
        : null,
    'cors_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('HUMIFY_CORS_ORIGINS', 'http://localhost:3000,http://127.0.0.1:3000'))
    ))),
    'signed_url_ttl_minutes' => (int) env('HUMIFY_SIGNED_URL_TTL_MINUTES', 5),
    'work_location' => [
        'name' => env('HUMIFY_DEMO_LOCATION_NAME', 'Demo Office'),
        'latitude' => (float) env('HUMIFY_DEMO_LOCATION_LAT', -6.200000),
        'longitude' => (float) env('HUMIFY_DEMO_LOCATION_LNG', 106.816666),
        'radius_meters' => (int) env('HUMIFY_DEMO_LOCATION_RADIUS', 100),
    ],
    'face_verifier' => [
        'driver' => env('HUMIFY_FACE_VERIFIER_DRIVER', 'fake'),
        'fake' => [
            'matched' => filter_var(env('HUMIFY_FACE_FAKE_MATCHED', true), FILTER_VALIDATE_BOOL),
            'score' => (float) env('HUMIFY_FACE_FAKE_SCORE', 95),
            'throw' => filter_var(env('HUMIFY_FACE_FAKE_THROW', false), FILTER_VALIDATE_BOOL),
        ],
        'http' => [
            'base_url' => env('HUMIFY_FACE_HTTP_BASE_URL'),
            'api_key' => env('HUMIFY_FACE_HTTP_API_KEY'),
            'timeout' => (int) env('HUMIFY_FACE_HTTP_TIMEOUT', 10),
        ],
    ],
];
