<?php

declare(strict_types=1);

namespace App\Services\Public\Post;

use App\Events\Public\Post\PublicCommentCreated;
use App\Events\Public\Post\PublicCommentDeleted;
use App\Events\Public\Post\PublicCommentUpdated;
use App\Events\Public\Post\PublicPostCountsUpdated;
use App\Models\Post\Comment;
use App\Models\Post\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CommentService
{
    public function create(Post $post, User $user, string $body, ?int $parentId = null): Comment
    {
        return DB::transaction(function () use ($post, $user, $body, $parentId) {
            $comment = Comment::query()->create([
                'post_id' => $post->id,
                'user_id' => $user->id,
                'parent_id' => $parentId,
                'body' => $body,
            ]);

            event(new PublicCommentCreated($comment));

            $count = (int) Comment::query()->where('post_id', $post->id)->count();
            event(new PublicPostCountsUpdated((string) $post->id, ['comments' => $count]));

            return $comment;
        });
    }

    public function update(Comment $comment, User $user, string $body): Comment
    {
        if ((string) $comment->user_id !== (string) $user->id) {
            abort(403);
        }

        return DB::transaction(function () use ($comment, $body) {
            $comment->body = $body;
            $comment->save();

            event(new PublicCommentUpdated($comment));

            return $comment;
        });
    }

    public function delete(Comment $comment, User $user): void
    {
        if ((string) $comment->user_id !== (string) $user->id) {
            abort(403);
        }

        DB::transaction(function () use ($comment) {
            $postId = (int) $comment->post_id;
            $id = (int) $comment->id;

            $comment->delete();

            event(new PublicCommentDeleted((string) $id, (string) $postId));

            $count = (int) Comment::where('post_id', $postId)->count();
            event(new PublicPostCountsUpdated((string) $postId, ['comments' => $count]));
        });
    }
}
