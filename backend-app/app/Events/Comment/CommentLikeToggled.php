<?php

namespace App\Events\Comment;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class CommentLikeToggled implements ShouldBroadcastNow
{
    public function __construct(
        public int $postId,
        public int $commentId,
        public bool $liked,
        public int $count
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("public.comments.{$this->postId}");
    }

    public function broadcastAs(): string
    {
        return 'comment.like.toggled';
    }

    public function broadcastWith(): array
    {
        return [
            'comment_id' => $this->commentId,
            'liked' => $this->liked,
            'count' => $this->count,
        ];
    }
}
