<?php

namespace App\Http\Resources\Comment;

use App\Http\Resources\PublicApi\PublicMiniUserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray($request): array
    {
        $c = $this->resource;

        return [
            'id' => $c->id,
            'commentable_type' => $c->commentable_type,
            'commentable_id' => $c->commentable_id,
            'parent_id' => $c->parent_id,
            'root_id' => $c->root_id,
            'depth' => $c->depth,
            'path' => $c->path,
            'body' => $c->body,
            'status' => $c->status,
            'replies_count' => $c->replies_count,
            'reactions_count' => $c->reactions_count,
            'liked_by_me' => (bool) ($c->liked_by_me ?? 0),
            'user' => PublicMiniUserResource::make($this->whenLoaded('user')),
            'user_id' => $c->user_id,
            'created_at' => optional($c->created_at)->toIso8601String(),
            'updated_at' => optional($c->updated_at)->toIso8601String(),
        ];
    }
}
