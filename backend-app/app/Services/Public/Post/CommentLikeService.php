<?php

declare(strict_types=1);

namespace App\Services\Public\Post;

use App\Events\Public\Post\PublicCommentCountsUpdated;
use App\Models\Post\Comment;
use App\Models\Post\CommentLike;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CommentLikeService
{
    public function toggle(Comment $comment, User $user): array
    {
        return DB::transaction(function () use ($comment, $user) {
            $existing = CommentLike::query()
                ->where('comment_id', $comment->id)
                ->where('user_id', $user->id)
                ->first();

            $liked = false;
            if ($existing) {
                $existing->delete();
                $liked = false;
            } else {
                CommentLike::query()->create([
                    'comment_id' => $comment->id,
                    'user_id' => $user->id,
                ]);
                $liked = true;
            }

            $likes = (int) CommentLike::query()->where('comment_id', $comment->id)->count();

            event(new PublicCommentCountsUpdated(
                (string) $comment->id,
                (string) $comment->post_id,
                ['likes' => $likes]
            ));

            return ['liked' => $liked, 'likes' => $likes];
        });
    }
}
