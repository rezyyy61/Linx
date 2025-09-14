<?php

declare(strict_types=1);

namespace App\Services\Public\Post;

use App\Events\Public\Post\PublicPostCountsUpdated;
use App\Models\Post\Post;
use App\Models\Post\PostSave;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PostSaveService
{
    public function toggle(Post $post, User $user): array
    {
        return DB::transaction(function () use ($post, $user) {
            $existing = PostSave::query()
                ->where('post_id', $post->id)
                ->where('user_id', $user->id)
                ->first();

            $saved = false;

            if ($existing) {
                $existing->delete();
                $saved = false;
            } else {
                PostSave::query()->create([
                    'post_id' => $post->id,
                    'user_id' => $user->id,
                ]);
                $saved = true;
            }

            $saves = (int) PostSave::query()->where('post_id', $post->id)->count();

            event(new PublicPostCountsUpdated((string) $post->id, [
                'saves' => $saves,
            ]));

            return [
                'saved' => $saved,
                'saves' => $saves,
            ];
        });
    }
}
