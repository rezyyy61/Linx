<?php

declare(strict_types=1);

namespace App\Services\Post;

use App\Models\Media;
use App\Models\Post\Post as PostModel;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PostService
{
    private const MAX_MEDIA = 10;

    private const COLLECTION = 'post';

    private const STATUS_PUBLISHED = 'published';

    private const STATUS_DRAFT = 'draft';

    public function create(array $data, User $user): PostModel
    {
        return DB::transaction(function () use ($data, $user) {
            $post = new PostModel;
            $post->user_id = $user->id;
            $post->content = $data['content'] ?? null;
            $post->visibility = $data['visibility'] ?? 'public';

            $status = $data['status'] ?? self::STATUS_DRAFT;
            $post->status = $status;
            $post->published_at = $status === self::STATUS_PUBLISHED ? now() : null;

            $post->save();

            if (array_key_exists('media', $data)) {
                $this->syncMedia($post, $data['media']);
            }

            return $this->loadPostMedia($post);
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

                if ($post->status === self::STATUS_PUBLISHED) {
                    if (is_null($post->published_at)) {
                        $post->published_at = now();
                    }
                } elseif ($post->status === self::STATUS_DRAFT) {
                    $post->published_at = null;
                }
            }

            $post->save();

            if (array_key_exists('media', $data)) {
                $this->syncMedia($post, $data['media']);
            }

            return $this->loadPostMedia($post);
        });
    }

    public function delete(PostModel $post): void
    {
        DB::transaction(function () use ($post) {
            $post->media()->wherePivot('collection', self::COLLECTION)->detach();
            $post->delete();
        });
    }

    private function syncMedia(PostModel $post, ?array $mediaItems): void
    {
        if ($mediaItems === null) {
            return;
        }

        if ($mediaItems === []) {
            $post->media()->wherePivot('collection', self::COLLECTION)->detach();

            return;
        }

        $prepared = [];
        foreach ($mediaItems as $idx => $item) {
            if (! isset($item['id'])) {
                continue;
            }
            $id = (int) $item['id'];
            $order = isset($item['order']) ? (int) $item['order'] : $idx;
            $prepared[$id] = ['collection' => self::COLLECTION, 'order_column' => $order];
            if (count($prepared) >= self::MAX_MEDIA) {
                break;
            }
        }

        if ($prepared === []) {
            $post->media()->wherePivot('collection', self::COLLECTION)->detach();

            return;
        }

        $existingIds = Media::query()
            ->whereIn('id', array_keys($prepared))
            ->pluck('id')
            ->all();

        if ($existingIds === []) {
            $post->media()->wherePivot('collection', self::COLLECTION)->detach();

            return;
        }

        $currentIds = $post->media()
            ->wherePivot('collection', self::COLLECTION)
            ->pluck('media.id')
            ->all();

        $desiredIds = $existingIds;
        $toDetach = array_diff($currentIds, $desiredIds);
        if (! empty($toDetach)) {
            $post->media()->wherePivot('collection', self::COLLECTION)->detach($toDetach);
        }

        foreach ($desiredIds as $id) {
            $attrs = $prepared[$id];
            if (in_array($id, $currentIds, true)) {
                $post->media()->updateExistingPivot($id, $attrs);
            } else {
                $post->media()->attach($id, $attrs + ['created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    private function loadPostMedia(PostModel $post): PostModel
    {
        return $post->load(['media' => function ($q) {
            $q->wherePivot('collection', self::COLLECTION)->orderBy('mediables.order_column');
        }]);
    }
}
