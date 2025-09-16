<?php

namespace App\Notifications\Comment;

use App\Notifications\Follow\BaseFollowNotification;

class Mentioned extends BaseFollowNotification
{
    public function __construct(
        public array $actor,
        public int $postId,
        public int $commentId,
        public ?string $snippet = null,
    ) {}

    public function toDatabaseCustom($notifiable): array
    {
        return [
            'kind' => 'comment.mentioned',
            'actor' => [
                'id' => (int) ($this->actor['id'] ?? 0),
                'slug' => $this->actor['slug'] ?? null,
                'username' => $this->actor['username'] ?? null,
                'avatar' => $this->actor['avatar'] ?? null,
            ],
            'post_id' => $this->postId,
            'comment_id' => $this->commentId,
            'snippet' => $this->snippet,
        ];
    }
}
