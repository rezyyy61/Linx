<?php

declare(strict_types=1);

namespace App\Services\Post;

use App\Enums\MediaStatus;
use App\Models\Media;
use App\Models\Post\Post as PostModel;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PostService
{
    public function create(array $data, User $user): PostModel
    {
        return DB::transaction(function () use ($data, $user) {
            $post = new PostModel;
            $post->user_id = $user->id;
            $post->content = $data['content'] ?? null;
            $post->visibility = $data['visibility'] ?? 'public';
            $post->status = $data['status'] ?? 'published';
            $post->published_at = $post->status === 'published' ? now() : null;
            $post->save();

            $this->syncMedia($post, $data['media'] ?? []);

            return $post->load('media');
        });
    }

    public function update(PostModel $post, array $data): PostModel
    {
        return DB::transaction(function () use ($post, $data) {
            if (array_key_exists('content', $data)) {
                $post->content = $data['content'];
            }
            if (array_key_exists('visibility', $data)) {
                $post->visibility = $data['visibility'];
            }
            if (array_key_exists('status', $data)) {
                $post->status = $data['status'];
                if ($post->status === 'published' && is_null($post->published_at)) {
                    $post->published_at = now();
                }
            }
            $post->save();

            if (array_key_exists('media', $data)) {
                $this->syncMedia($post, $data['media']);
            }

            return $post->load('media');
        });
    }

    public function delete(PostModel $post): void
    {
        DB::transaction(function () use ($post) {
            $post->media()->detach();
            $post->delete();
        });
    }

    private function syncMedia(PostModel $post, ?array $mediaItems): void
    {
        if (empty($mediaItems)) {
            $post->media()->detach();

            return;
        }

        $prepared = [];
        foreach ($mediaItems as $item) {
            if (! isset($item['id'])) {
                continue;
            }
            $id = (int) $item['id'];
            $order = isset($item['order']) ? (int) $item['order'] : 0;
            $prepared[$id] = ['collection' => 'post', 'order_column' => $order];
        }

        if ($prepared === []) {
            $post->media()->detach();

            return;
        }

        $validIds = Media::query()
            ->whereIn('id', array_keys($prepared))
            ->where('status', MediaStatus::READY)
            ->pluck('id')
            ->all();

        $sync = [];
        foreach ($validIds as $id) {
            $sync[$id] = $prepared[$id];
        }

        $post->media()->sync($sync);
    }
}
