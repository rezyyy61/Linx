<?php

declare(strict_types=1);

namespace App\Events\Public\Post;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class PublicCommentCountsUpdated implements ShouldBroadcast
{
    public function __construct(
        public string $id,       // comment id
        public string $postId,   // post id
        public array $counts     // e.g. ['likes'=>123]
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('public.posts')];
    }

    public function broadcastAs(): string
    {
        return 'public.comment.counts.updated';
    }

    public function broadcastWith(): array
    {
        return ['id' => $this->id, 'post_id' => $this->postId, 'counts' => $this->counts];
    }
}
