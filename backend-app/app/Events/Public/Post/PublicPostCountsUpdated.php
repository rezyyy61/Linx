<?php

declare(strict_types=1);

namespace App\Events\Public\Post;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class PublicPostCountsUpdated implements ShouldBroadcastNow
{
    public function __construct(public string $id, public array $counts) {}

    public function broadcastOn(): array
    {
        return [new Channel('public.posts')];
    }

    public function broadcastAs(): string
    {
        return 'public.post.counts.updated';
    }

    public function broadcastWith(): array
    {
        return ['id' => $this->id, 'counts' => $this->counts];
    }
}
