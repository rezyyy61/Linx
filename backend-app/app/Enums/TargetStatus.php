<?php

namespace App\Enums;

enum TargetStatus: string
{
    case PENDING = 'pending';
    case SENT = 'sent';
    case FAILED = 'failed';
    case OPENED = 'opened';
    case CLICKED = 'clicked';
}
