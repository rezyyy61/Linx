<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\Events\JobFailed;

class JobFailedAlert extends Notification
{
    use Queueable;

    public function __construct(public JobFailed $event) {}

    public function via($notifiable): array
    {
        $channels = [];

        if (config('alerts.mail')) {
            $channels[] = 'mail';
        }
        if (config('alerts.slack_webhook')) {
            $channels[] = 'slack';
        }

        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        $e = $this->event;
        $name = $e->job->resolveName();
        $payload = method_exists($e->job, 'payload') ? json_encode($e->job->payload()) : '';

        $exceptionMessage = $e->exception->getMessage();

        return (new MailMessage)
            ->subject("Job Failed: {$name}")
            ->line("Connection: {$e->connectionName}")
            ->line("Queue: {$e->job->getQueue()}")
            ->line("Exception: {$exceptionMessage}")
            ->line("Job: {$name}")
            ->lineIf(! empty($payload), "Payload: {$payload}")
            ->line('Check Horizon → Failed Jobs for full trace.');
    }

    public function toSlack($notifiable): SlackMessage
    {
        $e = $this->event;
        $name = $e->job->resolveName();

        return (new SlackMessage)
            ->error()
            ->content('🚨 Job Failed')
            ->attachment(function ($attachment) use ($e, $name) {
                $attachment->title($name)
                    ->fields([
                        'Connection' => $e->connectionName,
                        'Queue' => $e->job->getQueue(),
                        'Exception' => $e->exception->getMessage(),
                    ]);
            });
    }
}
