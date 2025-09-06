<?php

namespace App\Events\Follow;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class FollowRequestCreated implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public int $request_id,
        public int $actor_id,
        public int $target_id,
        public array $actor
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('user.'.$this->target_id);
    }

    public function broadcastAs(): string
    {
        return 'follow.request.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->request_id,
            'actor_id' => $this->actor_id,
            'target_id' => $this->target_id,
            'actor' => $this->actor,
        ];
    }
}
