<?php

namespace App\Events\Comment;

use App\Http\Resources\Comment\CommentResource;
use App\Models\Comment\Comment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class CommentUpdated implements ShouldBroadcastNow
{
    public function __construct(public Comment $comment) {}

    public function broadcastOn(): Channel
    {
        return new Channel("public.comments.{$this->comment->commentable_id}");
    }

    public function broadcastAs(): string
    {
        return 'comment.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'comment' => (new CommentResource(
                $this->comment->loadMissing('user.profile.translations', 'user.profile.logo')
            ))->toArray(request()),
        ];
    }
}
