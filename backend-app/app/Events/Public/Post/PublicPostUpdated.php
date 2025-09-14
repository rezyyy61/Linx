<?php

declare(strict_types=1);

namespace App\Events\Public\Post;

use App\Http\Resources\PublicApi\PublicPostResource;
use App\Models\Post\Post;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class PublicPostUpdated implements ShouldBroadcastNow
{
    public function __construct(public Post $post) {}

    public function broadcastOn(): Channel
    {
        return new Channel('public.posts');
    }

    public function broadcastAs(): string
    {
        return 'public.post.updated';
    }

    public function broadcastWith(): array
    {
        return ['post' => (new PublicPostResource($this->post->loadMissing(
            'user', 'user.profile', 'user.profile.translations', 'user.profile.logo', 'user.profile.media', 'media'
        )))->toArray(request())];
    }
}
