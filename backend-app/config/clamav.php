<?php

return [
    'enabled' => env('SCAN_ENABLED', true),
    'host' => env('CLAMAV_HOST', 'clamav'),
    'port' => env('CLAMAV_PORT', 3310),
    'timeout' => 30,
    'max_bytes' => env('SCAN_MAX_BYTES', 52_428_800),
];
