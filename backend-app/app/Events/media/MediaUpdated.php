<?php

namespace App\Events\media;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MediaUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public int $mediaId;

    public array $payload;

    public bool $afterCommit = true;

    public function __construct(int $mediaId, array $payload = [])
    {
        $this->mediaId = $mediaId;
        $this->payload = $payload;
    }

    public function broadcastOn(): PrivateChannel
    {
        $name = 'media.'.$this->mediaId;

        return new PrivateChannel($name);
    }

    public function broadcastAs(): string
    {
        return 'MediaUpdated';
    }

    public function broadcastWith(): array
    {
        $data = $this->payload + [
            'media_id' => $this->mediaId,
            'ts' => now()->toISOString(),
        ];

        return $data;
    }
}
