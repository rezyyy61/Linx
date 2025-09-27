<?php

declare(strict_types=1);

namespace App\Http\Resources\Announcement;

use App\Http\Resources\PublicApi\PublicMiniUserResource;
use App\Models\Announcement\Announcement;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Announcement
 */
class AnnouncementResource extends JsonResource
{
    public function toArray($request): array
    {
        $announcement = $this->resource;

        return [
            'id' => (int) $announcement->id,
            'owner_id' => (int) $announcement->owner_id,
            'owner' => $this->whenLoaded('owner', function () use ($announcement) {
                return new PublicMiniUserResource($announcement->owner);
            }),
            'title' => $announcement->title,
            'slug' => $announcement->slug,
            'body' => $announcement->body,
            'is_pinned' => (bool) $announcement->is_pinned,
            'visibility' => $announcement->visibility,
            'publish_at' => optional($announcement->publish_at)?->toISOString(),
            'cover_url' => $this->whenLoaded('covers', function () use ($announcement) {
                $m = $announcement->covers->sortBy('pivot.order_column')->first();

                return $m && method_exists($m, 'publicUrl') ? $m->publicUrl() : null;
            }),
            'cover_id' => $this->whenLoaded('covers', function () use ($announcement) {
                return (int) $announcement->covers->sortBy('pivot.order_column')->value('id');
            }, null),
            'documents' => $this->whenLoaded('documents', function () use ($announcement) {
                return $announcement->documents->map(static fn ($m) => [
                    'id' => (int) $m->id,
                    'url' => method_exists($m, 'publicUrl') ? $m->publicUrl() : null,
                ])->values()->all();
            }),
            'document_ids' => $this->whenLoaded('documents', function () use ($announcement) {
                return $announcement->documents->pluck('id')->map(fn ($i) => (int) $i)->values()->all();
            }, []),
            'created_at' => optional($announcement->created_at)?->toISOString(),
            'updated_at' => optional($announcement->updated_at)?->toISOString(),
        ];
    }
}
