<?php

namespace App\Enums;

enum MediaStatus: string
{
    case CREATED = 'created';
    case UPLOADED = 'uploaded';
    case SCANNED = 'scanned';
    case PROCESSING = 'processing';
    case READY = 'ready';
    case REJECTED = 'rejected';
    case FAILED = 'failed';
}
