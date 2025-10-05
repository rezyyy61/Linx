<?php

declare(strict_types=1);

namespace App\Http\Resources\Publication;

use App\Http\Resources\PublicApi\PublicMiniUserResource;
use App\Models\Publication\Publication;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Publication
 */
class PublicationResource extends JsonResource
{
    public function toArray($request): array
    {
        $publication = $this->resource;

        return [
            'id' => (int) $publication->id,
            'owner_id' => (int) $publication->owner_id,
            'owner' => $this->whenLoaded('owner', function () use ($publication) {
                return new PublicMiniUserResource($publication->owner);
            }),
            'title' => (string) $publication->title,
            'slug' => (string) $publication->slug,
            'issue' => (string) $publication->issue,
            'description' => $publication->description,
            'is_published' => (bool) $publication->is_published,
            'publish_at' => optional($publication->publish_at)?->toISOString(),
            'cover_url' => $this->whenLoaded('covers', function () use ($publication) {
                $m = $publication->covers->sortBy('pivot.order_column')->first();

                return $m && method_exists($m, 'publicUrl') ? $m->publicUrl() : null;
            }),
            'cover_id' => $this->whenLoaded('covers', function () use ($publication) {
                return (int) $publication->covers->sortBy('pivot.order_column')->value('id');
            }, null),
            'documents' => $this->whenLoaded('documents', function () use ($publication) {
                return $publication->documents->map(static fn ($m) => [
                    'id' => (int) $m->id,
                    'url' => method_exists($m, 'publicUrl') ? $m->publicUrl() : null,
                ])->values()->all();
            }),
            'document_ids' => $this->whenLoaded('documents', function () use ($publication) {
                return $publication->documents->pluck('id')->map(fn ($i) => (int) $i)->values()->all();
            }, []),
            'language' => $publication->language,
            'created_at' => optional($publication->created_at)?->toISOString(),
            'updated_at' => optional($publication->updated_at)?->toISOString(),
        ];
    }
}
