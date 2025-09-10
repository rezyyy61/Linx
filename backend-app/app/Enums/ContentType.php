<?php

namespace App\Enums;

enum ContentType: string
{
    case NEWSLETTER = 'newsletter';
    case EVENT = 'event';
    case CAMPAIGN = 'campaign';
    case SURVEY = 'survey';
}
