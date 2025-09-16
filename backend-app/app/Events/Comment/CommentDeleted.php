<?php

namespace App\Events\Comment;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class CommentDeleted implements ShouldBroadcastNow
{
    public function __construct(
        public int $postId,
        public int $commentId,
        public ?int $parentId,
        public int $rootId,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("public.comments.{$this->postId}");
    }

    public function broadcastAs(): string
    {
        return 'comment.deleted';
    }

    public function broadcastWith(): array
    {
        return [
            'comment_id' => $this->commentId,
            'parent_id' => $this->parentId,
            'root_id' => $this->rootId,
        ];
    }
}
