<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;

class MediaRejectedAlert extends Notification
{
    use Queueable;

    public function __construct(
        public int $mediaId,
        public string $key,
        public string $reason
    ) {}

    public function via($notifiable): array
    {
        $ch = [];
        if (config('alerts.mail'))          $ch[] = 'mail';
        if (config('alerts.slack_webhook')) $ch[] = 'slack';
        return $ch;
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Media REJECTED (id: {$this->mediaId})")
            ->line("Key: {$this->key}")
            ->line("Reason: {$this->reason}")
            ->line('The file was deleted from object storage.');
    }

    public function toSlack($notifiable): SlackMessage
    {
        return (new SlackMessage)
            ->error()
            ->content('🚫 Media REJECTED by AV')
            ->to(config('alerts.slack_channel'))
            ->attachment(fn ($a) =>
            $a->title("media #{$this->mediaId}")
                ->fields([
                    'key'    => $this->key,
                    'reason' => $this->reason,
                ])
            );
    }
}
