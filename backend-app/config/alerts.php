<?php

return [
    'mail' => env('ALERTS_MAIL'),
    'slack_webhook' => env('ALERTS_SLACK_WEBHOOK_URL'),
    'slack_channel' => env('ALERTS_SLACK_CHANNEL', '#ops'),

    'notify' => [
        'job_failed' => true,
        'media_rejected' => true,
        'media_failed' => true,
    ],
];
