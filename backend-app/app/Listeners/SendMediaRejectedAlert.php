<?php

namespace App\Listeners;

use App\Events\media\MediaRejected;
use App\Notifications\MediaRejectedAlert;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendMediaRejectedAlert implements ShouldQueue
{
    public function handle(MediaRejected $event): void
    {
        if (! config('alerts.notify.media_rejected')) {
            return;
        }

        if ($mail = config('alerts.mail')) {
            Notification::route('mail', $mail)->notify(
                new MediaRejectedAlert($event->mediaId, $event->key, $event->reason)
            );
        }
        if ($hook = config('alerts.slack_webhook')) {
            Notification::route('slack', $hook)->notify(
                new MediaRejectedAlert($event->mediaId, $event->key, $event->reason)
            );
        }
    }
}
