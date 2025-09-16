<?php

namespace App\Notifications\Comment;

use App\Notifications\Follow\BaseFollowNotification;

class OnPost extends BaseFollowNotification
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
            'kind' => 'comment.on_post',
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
