<?php

declare(strict_types=1);

namespace App\Events\Public\Post;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class PublicPostDeleted implements ShouldBroadcastNow
{
    public function __construct(public int $id) {}

    public function broadcastOn(): Channel
    {
        return new Channel('public.posts');
    }

    public function broadcastAs(): string
    {
        return 'public.post.deleted';
    }

    public function broadcastWith(): array
    {
        return ['id' => $this->id];
    }
}
