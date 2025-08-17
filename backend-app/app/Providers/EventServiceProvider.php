<?php

namespace App\Providers;

use App\Events\MediaProcessingFailed;
use App\Events\MediaRejected;
use App\Listeners\SendMediaFailedAlert;
use App\Listeners\SendMediaRejectedAlert;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        MediaRejected::class         => [SendMediaRejectedAlert::class],
        MediaProcessingFailed::class => [SendMediaFailedAlert::class],
    ];
}
