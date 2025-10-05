<?php

declare(strict_types=1);

namespace App\Http\Resources\PublicApi;

use App\Contracts\PostableResourceable;
use App\Http\Resources\MediaResource;
use App\Models\Comment\Comment;
use App\Models\Media;
use App\Models\Post\Post as PostModel;
use App\Models\Post\PostLike;
use App\Models\Post\PostSave;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var PostModel|Model $post */
        $post = $this->resource;
        if (! $post instanceof PostModel) {
            return [];
        }

        $post->loadMissing([
            'postable',
            'original.postable',
        ]);

        $authUser = $request->user();

        $liked = false;
        if ($authUser) {
            $liked = PostLike::query()
                ->where('post_id', $post->id)
                ->where('user_id', $authUser->getKey())
                ->exists();
        }

        $saved = false;
        if ($authUser) {
            $saved = PostSave::query()
                ->where('post_id', $post->id)
                ->where('user_id', $authUser->getKey())
                ->exists();
        }

        $user = $post->user instanceof User ? $post->user : null;
        $profile = $user?->profile;
        $translations = $profile && $profile->relationLoaded('translations') ? $profile->translations : collect();
        $tr = $translations->firstWhere('locale', app()->getLocale()) ?: $translations->first();
        $name = ($tr->name ?? $tr->title ?? null) ?? $profile->name ?? $user?->name;

        $logoMedia = null;
        if ($profile) {
            if ($profile->relationLoaded('logo')) {
                $logoMedia = $profile->logo->first();
            } elseif ($profile->relationLoaded('media')) {
                $logoMedia = $profile->media->first(fn ($m) => ($m->pivot->collection ?? null) === 'logo');
            } else {
                $logoMedia = $profile->logo()->first();
            }
        }

        $avatar = $logoMedia instanceof Media ? $logoMedia->publicUrl() : null;

        $likes = (int) PostLike::query()->where('post_id', $post->id)->count();
        $saves = (int) PostSave::query()->where('post_id', $post->id)->count();
        $comments = (int) ($post->comments_count ?? Comment::query()
            ->where('commentable_type', \App\Models\Post\Post::class)
            ->where('commentable_id', $post->id)
            ->where('status', 'visible')
            ->count());

        $shares = (int) ($post->shares_count ?? $post->shares()->active()->count());

        $postableAlias = null;
        $postableSlug = null;
        $postablePayload = null;
        $postableTypeOut = $post->postable_type;
        $postableIdOut = $post->postable_id;

        $shareable = $post->getRelationValue('postable');

        if (! ($shareable instanceof PostableResourceable)) {
            $original = $post->getRelationValue('original');
            $origShareable = $original instanceof Model ? $original->getRelationValue('postable') : null;

            if ($origShareable instanceof PostableResourceable) {
                $shareable = $origShareable;
                $postableTypeOut = get_class($shareable);
                $postableIdOut = $shareable instanceof Model ? $shareable->getKey() : null;
            }
        }

        if ($shareable instanceof PostableResourceable) {
            $postableAlias = $shareable->getPostableAlias();
            $postableSlug = $shareable->getPostableSlug();
            $postablePayload = $shareable->toPostableResource();
        }

        return [
            'id' => $post->id,
            'content' => $post->content,
            'visibility' => $post->visibility,
            'status' => $post->status,
            'repost_of_id' => $post->repost_of_id,
            'published_at' => optional($post->published_at)?->toIso8601String(),
            'created_at' => $post->created_at->toIso8601String(),
            'updated_at' => $post->updated_at->toIso8601String(),
            'liked' => $liked,
            'saved' => $saved,
            'author' => [
                'id' => $post->user_id,
                'name' => $name,
                'slug' => $profile->slug ?? null,
                'avatar' => $avatar,
                'avatarColor' => $profile->avatar_color ?? null,
            ],
            'counts' => [
                'likes' => $likes,
                'comments' => $comments,
                'shares' => $shares,
                'saves' => $saves,
                'views' => 0,
            ],
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'postable_type' => $postableTypeOut,
            'postable_id' => $postableIdOut,
            'postable_alias' => $postableAlias,
            'postable_slug' => $postableSlug,
            'postable' => $postablePayload,
        ];
    }
}
