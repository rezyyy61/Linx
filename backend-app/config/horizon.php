<?php

use Illuminate\Support\Str;

// فلگ کنترل مصرف صف media توسط Horizon در محیط local (پیش‌فرض: خاموش)
$consumeMediaLocal = filter_var(env('HORIZON_LOCAL_CONSUME_MEDIA', false), FILTER_VALIDATE_BOOL);

return [

    /*
    |--------------------------------------------------------------------------
    | Horizon Domain
    |--------------------------------------------------------------------------
    */
    'domain' => env('HORIZON_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Path
    |--------------------------------------------------------------------------
    */
    'path' => env('HORIZON_PATH', 'horizon'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Connection
    |--------------------------------------------------------------------------
    */
    'use' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Prefix
    |--------------------------------------------------------------------------
    */
    'prefix' => env(
        'HORIZON_PREFIX',
        Str::slug(env('APP_NAME', 'laravel'), '_').'_horizon:'
    ),

    /*
    |--------------------------------------------------------------------------
    | Horizon Route Middleware
    |--------------------------------------------------------------------------
    */
    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Queue Wait Time Thresholds
    |--------------------------------------------------------------------------
    | فرمت صحیح: 'connection:queue' => seconds
    */
    'waits' => [
        'redis:scan' => 15,
        'redis:media' => 30,
        'redis:default' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Trimming Times (minutes)
    |--------------------------------------------------------------------------
    */
    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    /*
    |--------------------------------------------------------------------------
    | Silenced Jobs
    |--------------------------------------------------------------------------
    */
    'silenced' => [
        // App\Jobs\SomethingNoisy::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics
    |--------------------------------------------------------------------------
    */
    'metrics' => [
        'trim_snapshots' => [
            'job' => 24,
            'queue' => 24,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fast Termination
    |--------------------------------------------------------------------------
    */
    'fast_termination' => false,

    /*
    |--------------------------------------------------------------------------
    | Memory Limit (MB)
    |--------------------------------------------------------------------------
    */
    'memory_limit' => 128,

    /*
    |--------------------------------------------------------------------------
    | Queue Worker Configuration (base defaults)
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'supervisor' => [
            'connection' => 'redis',
            'queue' => ['default'],
            'balance' => 'auto',   // simple|auto|false
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 1,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 256,
            'tries' => 3,
            'timeout' => 120,
            'nice' => 0,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Environment Specific Configuration
    |--------------------------------------------------------------------------
    | در local، supervisor مربوط به media فقط وقتی اضافه می‌شود که
    | HORIZON_LOCAL_CONSUME_MEDIA=true باشد (پیش‌فرض: false).
    */
    'environments' => [

        'production' => [
            'supervisor-scan' => [
                'connection' => 'redis',
                'queue' => ['scan'],
                'balance' => 'simple',
                'minProcesses' => 2,
                'maxProcesses' => 10,
                'tries' => 1,
                'timeout' => 60,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
            ],
            'supervisor-media' => [
                'connection' => 'redis',
                'queue' => ['media'],
                'balance' => 'simple',
                'minProcesses' => 2,
                'maxProcesses' => 10,
                'tries' => 2,
                'timeout' => 900,
                'memory' => 512,
            ],
            'supervisor-default' => [
                'connection' => 'redis',
                'queue' => ['default'],
                'balance' => 'auto',
                'minProcesses' => 2,
                'maxProcesses' => 10,
                'tries' => 3,
                'timeout' => 120,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
            ],
        ],

        'local' => array_filter([
            'supervisor-scan' => [
                'connection' => 'redis',
                'queue' => ['scan'],
                'balance' => 'simple',
                'minProcesses' => 1,
                'maxProcesses' => 2,
                'tries' => 1,
                'timeout' => 60,
            ],

            // فقط اگر بخواهی Horizon در local صف media را هم مصرف کند:
            $consumeMediaLocal ? 'supervisor-media' : null => $consumeMediaLocal ? [
                'connection' => 'redis',
                'queue' => ['media'],
                'balance' => 'simple',
                'minProcesses' => 1,
                'maxProcesses' => 2,
                'tries' => 2,
                'timeout' => 900,
                'memory' => 512,
            ] : null,

            'supervisor-default' => [
                'connection' => 'redis',
                'queue' => ['default'],
                'balance' => 'auto',
                'minProcesses' => 1,
                'maxProcesses' => 3,
                'tries' => 3,
                'timeout' => 120,
            ],
        ], static fn ($v, $k) => $v !== null && $k !== null, ARRAY_FILTER_USE_BOTH),
    ],

    'enabled' => env('HORIZON_ENABLED', true),
    'ip_allow' => env('HORIZON_IP_ALLOW', ''),
    'allowed_emails' => env('HORIZON_ALLOWED_EMAILS', ''),
    'slack_webhook_url' => env('HORIZON_SLACK_WEBHOOK_URL'),
    'slack_channel' => env('HORIZON_SLACK_CHANNEL', '#general'),
    'notify_mail' => env('HORIZON_NOTIFY_MAIL'),
];
