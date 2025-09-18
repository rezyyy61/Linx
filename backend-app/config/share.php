<?php

declare(strict_types=1);

return [
    'route_prefix' => 's',
    'utm' => [
        'medium' => 'share',
    ],
    'ip_salt' => env('SHARE_IP_SALT', ''),
    'channels' => [
        'internal',
        'webshare',
        'whatsapp',
        'telegram',
        'facebook',
        'email',
        'embed',
        'repost',
    ],
    'base_url' => env('APP_URL', 'http://localhost'),
    'aliases' => [
        'post' => App\Models\Post\Post::class,
    ],
];
