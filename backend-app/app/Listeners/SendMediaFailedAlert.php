<?php

namespace App\Listeners;

use App\Events\media\MediaProcessingFailed;
use App\Notifications\MediaFailedAlert;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendMediaFailedAlert implements ShouldQueue
{
    public function handle(MediaProcessingFailed $event): void
    {
        if (! config('alerts.notify.media_failed')) {
            return;
        }

        if ($mail = config('alerts.mail')) {
            Notification::route('mail', $mail)->notify(
                new MediaFailedAlert($event->mediaId, $event->key, $event->reason)
            );
        }
        if ($hook = config('alerts.slack_webhook')) {
            Notification::route('slack', $hook)->notify(
                new MediaFailedAlert($event->mediaId, $event->key, $event->reason)
            );
        }
    }
}
