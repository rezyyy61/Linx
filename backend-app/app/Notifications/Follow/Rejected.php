<?php

namespace App\Notifications\Follow;

class Rejected extends BaseFollowNotification
{
    public function __construct(public array $actor) {}

    public function toDatabaseCustom($notifiable): array
    {
        return [
            'kind' => 'follow.rejected',
            'actor' => [
                'id' => (int) ($this->actor['id'] ?? 0),
                'slug' => $this->actor['slug'] ?? null,
                'username' => $this->actor['username'] ?? null,
                'avatar' => $this->actor['avatar'] ?? null,
            ],
        ];
    }
}
