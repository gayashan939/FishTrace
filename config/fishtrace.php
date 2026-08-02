<?php

return [
    'firebase' => [
        'driver' => env('FIREBASE_DRIVER', 'mock'),
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'database_url' => env('FIREBASE_DATABASE_URL', 'https://mock.local'),
        'credentials' => env('FIREBASE_CREDENTIALS'),
        'telemetry_retention_hours' => (int) env('FIREBASE_TELEMETRY_RETENTION_HOURS', 48),
        'sync_batch_size' => (int) env('FIREBASE_SYNC_BATCH_SIZE', 250),
        'sync_device_limit' => (int) env('FIREBASE_SYNC_DEVICE_LIMIT', 50),
        'sync_lock_seconds' => (int) env('FIREBASE_SYNC_LOCK_SECONDS', 55),
        'custom_token_ttl_seconds' => (int) env('FIREBASE_CUSTOM_TOKEN_TTL_SECONDS', 3600),
    ],
    'ai' => ['driver' => env('AI_DRIVER', 'mock'), 'url' => env('AI_SERVICE_URL'), 'token' => env('AI_SERVICE_TOKEN'), 'timeout' => (int) env('AI_REQUEST_TIMEOUT', 10), 'auto_predict' => (bool) env('AI_AUTO_PREDICT', true)],
    'blockchain' => ['driver' => env('BLOCKCHAIN_DRIVER', 'mock'), 'url' => env('BLOCKCHAIN_SERVICE_URL'), 'token' => env('BLOCKCHAIN_SERVICE_TOKEN'), 'network' => env('BLOCKCHAIN_NETWORK', 'mock'), 'contract' => env('BLOCKCHAIN_CONTRACT_ADDRESS'), 'auto_anchor' => (bool) env('BLOCKCHAIN_AUTO_ANCHOR', true), 'poll_batch_size' => (int) env('BLOCKCHAIN_POLL_BATCH_SIZE', 100)],
    'audit' => ['retention_days' => (int) env('AUDIT_RETENTION_DAYS', 2555)],
    'public_trace_cache_seconds' => (int) env('PUBLIC_TRACE_CACHE_SECONDS', 60),
];
