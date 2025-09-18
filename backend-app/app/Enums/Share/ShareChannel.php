<?php

declare(strict_types=1);

namespace App\Enums\Share;

enum ShareChannel: string
{
    case INTERNAL = 'internal';
    case WEBSHARE = 'webshare';
    case WHATSAPP = 'whatsapp';
    case TELEGRAM = 'telegram';
    case FACEBOOK = 'facebook';
    case EMAIL = 'email';
    case EMBED = 'embed';
    case REPOST = 'repost';
}
