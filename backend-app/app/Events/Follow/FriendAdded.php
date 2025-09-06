<?php

namespace App\Events\Follow;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class FriendAdded implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public int $user_id,
        public array $friend
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('user.'.$this->user_id);
    }

    public function broadcastAs(): string
    {
        return 'friend.added';
    }

    public function broadcastWith(): array
    {
        return [
            'friend' => $this->friend,
        ];
    }
}
