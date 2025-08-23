<?php

namespace App\Http\Resources\Profile;

use App\Http\Resources\MediaResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    public function toArray($request): array
    {
        $r = $this->resource;

        $media = $this->whenLoaded('media');

        $group = function (string $collection) use ($media) {
            if (! $media) {
                return [];
            }

            $items = $media->filter(fn ($m) => ($m->pivot->collection ?? null) === $collection)
                ->sortBy(fn ($m) => $m->pivot->order_column ?? 0)
                ->values();

            return MediaResource::collection($items);
        };

        $publishedAt = $r->getAttribute('published_at');
        $createdAt = $r->getAttribute('created_at');
        $updatedAt = $r->getAttribute('updated_at');

        return [
            'id' => $r->getAttribute('id'),
            'user_id' => $r->getAttribute('user_id'),
            'slug' => $r->getAttribute('slug'),
            'entity_type' => $r->getAttribute('entity_type'),
            'location' => $r->getAttribute('location'),
            'founded_year' => $r->getAttribute('founded_year'),
            'status' => $r->getAttribute('status'),
            'verified' => (bool) $r->getAttribute('verified'),
            'avatar_color' => $r->getAttribute('avatar_color'),
            'published_at' => $publishedAt ? $publishedAt->toIso8601String() : null,
            'created_at' => $createdAt ? $createdAt->toIso8601String() : null,
            'updated_at' => $updatedAt ? $updatedAt->toIso8601String() : null,

            'translations' => ProfileTranslationResource::collection($this->whenLoaded('translations')),
            'links' => ProfileLinkResource::collection($this->whenLoaded('links')),
            'values' => ProfileValueResource::collection($this->whenLoaded('values')),

            'logo' => $group('logo'),
            'documents' => $group('documents'),
            'cover' => $group('cover'),
            'gallery' => $group('gallery'),
        ];
    }
}
