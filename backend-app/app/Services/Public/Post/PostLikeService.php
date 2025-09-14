<?php

declare(strict_types=1);

namespace App\Services\Public\Post;

use App\Events\Public\Post\PublicPostCountsUpdated;
use App\Models\Post\Post;
use App\Models\Post\PostLike;
use App\Models\User;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PostLikeService
{
    public function toggle(Post $post, User $user): array
    {
        return DB::transaction(function () use ($post, $user) {
            $existing = PostLike::query()
                ->where('post_id', $post->id)
                ->where('user_id', $user->id)
                ->first();

            $liked = false;

            if ($existing) {
                $existing->delete();
                $liked = false;
            } else {
                PostLike::query()->create([
                    'post_id' => $post->id,
                    'user_id' => $user->id,
                ]);
                $liked = true;
            }

            $likes = (int) PostLike::query()->where('post_id', $post->id)->count();

            // invalidate preview cache
            Cache::forget($this->previewCacheKey($post->id));

            event(new PublicPostCountsUpdated((string) $post->id, [
                'likes' => $likes,
            ]));

            return [
                'liked' => $liked,
                'likes' => $likes,
            ];
        });
    }

    public function preview(Post $post, int $limit = 2): array
    {
        $key = $this->previewCacheKey($post->id);

        return Cache::remember($key, 60, function () use ($post, $limit) {
            $lastLikes = PostLike::query()
                ->where('post_id', $post->id)
                ->latest('created_at')
                ->with(['user.profile', 'user.profile.logo', 'user.profile.media', 'user.profile.translations'])
                ->limit($limit)
                ->get()
                ->pluck('user');

            $total = (int) PostLike::query()
                ->where('post_id', $post->id)
                ->count();

            return [
                'total' => $total,
                'users' => $lastLikes, // Collection<User>
            ];
        });
    }

    public function list(Post $post, int $limit = 30, ?string $cursor = null): CursorPaginator
    {
        return PostLike::query()
            ->where('post_id', $post->id)
            ->latest('created_at')
            ->with(['user.profile', 'user.profile.logo', 'user.profile.media', 'user.profile.translations'])
            ->cursorPaginate($limit, ['*'], 'cursor', $cursor);
    }

    private function previewCacheKey(int|string $postId): string
    {
        return "post:{$postId}:likes_preview";
    }
}
