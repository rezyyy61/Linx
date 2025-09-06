<?php

namespace App\Notifications\Follow;

use App\Channels\DatabaseCustomChannel;
use Illuminate\Notifications\Notification;

abstract class BaseFollowNotification extends Notification
{
    public function via($notifiable): array
    {
        return [DatabaseCustomChannel::class];
    }
}
