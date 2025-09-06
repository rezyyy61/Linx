<?php

namespace App\Channels;

use App\Events\Notifications\NotificationCreated;
use App\Models\Notifications\Notification as NotificationModel;
use Illuminate\Notifications\Notification;

class DatabaseCustomChannel
{
    public function send($notifiable, Notification $notification)
    {
        $payload = method_exists($notification, 'toDatabaseCustom')
            ? $notification->toDatabaseCustom($notifiable)
            : (method_exists($notification, 'toArray') ? $notification->toArray($notifiable) : []);

        $model = NotificationModel::create([
            'user_id' => $notifiable->getKey(),
            'type' => get_class($notification),
            'data' => $payload,
            'read_at' => null,
        ]);

        event(new NotificationCreated($model));
    }
}
