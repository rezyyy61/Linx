<?php

declare(strict_types=1);

namespace App\Http\Resources\PublicApi;

use App\Http\Resources\MediaResource;
use App\Models\Media;
use App\Models\Post\CommentLike;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicCommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $comment = $this->resource;

        $likedByMe = false;
        if ($request->user()) {
            $likedByMe = CommentLike::query()
                ->where('comment_id', $comment->id)
                ->where('user_id', $request->user()->id)
                ->exists();
        }

        $user = $comment->user instanceof User ? $comment->user : null;
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

        return [
            'id' => $comment->id,
            'post_id' => $comment->post_id,
            'parent_id' => $comment->parent_id,
            'body' => $comment->body,
            'created_at' => $comment->created_at?->toIso8601String(),
            'updated_at' => $comment->updated_at?->toIso8601String(),
            'author' => [
                'id' => $comment->user_id,
                'name' => $name,
                'slug' => $profile->slug ?? null,
                'avatar' => $avatar,
                'avatarColor' => $profile->avatar_color ?? null,
            ],
            'counts' => [
                'likes' => (int) CommentLike::where('comment_id', $comment->id)->count(),
                'replies' => 0,
            ],
            'liked' => $likedByMe,
            'media' => MediaResource::collection([]),
        ];
    }
}
