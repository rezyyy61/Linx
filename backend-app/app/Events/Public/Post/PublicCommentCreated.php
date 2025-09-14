<?php

declare(strict_types=1);

namespace App\Events\Public\Post;

use App\Http\Resources\PublicApi\PublicCommentResource;
use App\Models\Post\Comment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class PublicCommentCreated implements ShouldBroadcast
{
    public function __construct(public Comment $comment) {}

    public function broadcastOn(): array
    {
        return [new Channel('public.posts')];
    }

    public function broadcastAs(): string
    {
        return 'public.comment.created';
    }

    public function broadcastWith(): array
    {
        $comment = $this->comment->load(['user.profile.translations', 'user.profile.media']);

        return [
            'comment' => (new PublicCommentResource($comment))->toArray(app('request')),
        ];
    }
}
