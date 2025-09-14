<?php

declare(strict_types=1);

namespace App\Events\Public\Post;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class PublicCommentDeleted implements ShouldBroadcast
{
    public function __construct(public string $id, public string $postId) {}

    public function broadcastOn(): array
    {
        return [new Channel('public.posts')];
    }

    public function broadcastAs(): string
    {
        return 'public.comment.deleted';
    }

    public function broadcastWith(): array
    {
        return ['id' => $this->id, 'post_id' => $this->postId];
    }
}
