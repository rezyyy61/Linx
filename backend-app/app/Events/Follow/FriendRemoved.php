<?php

namespace App\Events\Follow;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FriendRemoved implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(public int $user_id, public int $other_id) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("user.{$this->user_id}")];
    }

    public function broadcastAs(): string
    {
        return 'friend.removed';
    }

    public function broadcastWith(): array
    {
        return [
            'other_id' => $this->other_id,
            'actor_id' => $this->user_id,
            'target_id' => $this->other_id,
        ];
    }
}
