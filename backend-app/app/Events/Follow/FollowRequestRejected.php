<?php

namespace App\Events\Follow;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class FollowRequestRejected implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public int $request_id,
        public int $actor_id,
        public int $target_id
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('user.'.$this->actor_id);
    }

    public function broadcastAs(): string
    {
        return 'follow.request.rejected';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->request_id,
            'actor_id' => $this->actor_id,
            'target_id' => $this->target_id,
        ];
    }
}
