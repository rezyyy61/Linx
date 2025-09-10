<?php

namespace App\Notifications\Member;

use App\Channels\DatabaseCustomChannel;
use Illuminate\Notifications\Notification;

class MembershipAccepted extends Notification
{
    public function __construct(public array $actor, public int $membershipId, public ?string $url = null) {}

    public function via($notifiable): array
    {
        return [DatabaseCustomChannel::class];
    }

    public function toDatabaseCustom($notifiable): array
    {
        return [
            'kind' => 'member.accepted',
            'actor' => [
                'id' => (int) ($this->actor['id'] ?? 0),
                'slug' => $this->actor['slug'] ?? null,
                'username' => $this->actor['username'] ?? null,
                'avatar' => $this->actor['avatar'] ?? null,
            ],
            'membership_id' => $this->membershipId,
            'url' => $this->url,
        ];
    }
}
