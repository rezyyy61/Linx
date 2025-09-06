<?php

namespace App\Notifications\Follow;

class Unfollowed extends BaseFollowNotification
{
    public function __construct(public array $actor) {}

    public function toDatabaseCustom($notifiable): array
    {
        return [
            'kind' => 'follow.unfollowed',
            'actor' => [
                'id' => (int) ($this->actor['id'] ?? 0),
                'slug' => $this->actor['slug'] ?? null,
                'username' => $this->actor['username'] ?? null,
                'avatar' => $this->actor['avatar'] ?? null,
            ],
        ];
    }
}
