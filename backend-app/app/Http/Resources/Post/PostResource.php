<?php

declare(strict_types=1);

namespace App\Http\Resources\Post;

use App\Http\Resources\MediaResource;
use App\Models\Post\Post as PostModel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $post = $this->resource;
        if (! $post instanceof PostModel) {
            return [];
        }

        $author = $post->user;
        $authorName = $author instanceof User ? $author->name : null;

        return [
            'id' => $post->id,
            'content' => $post->content,
            'visibility' => $post->visibility,
            'status' => $post->status,
            'published_at' => $post->published_at?->toIso8601String(),
            'created_at' => $post->created_at?->toIso8601String(),
            'updated_at' => $post->updated_at?->toIso8601String(),
            'author' => [
                'id' => $post->user_id,
                'name' => $authorName,
            ],
            'media' => MediaResource::collection($this->whenLoaded('media')),
        ];
    }
}
