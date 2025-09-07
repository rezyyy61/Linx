<?php

namespace App\Http\Resources\Announcement;

use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Announcement\Announcement */
class AnnouncementResource extends JsonResource
{
    public function toArray($request): array
    {
        $covers = $this->whenLoaded('covers', fn () => $this->covers->values(), collect());
        $documents = $this->whenLoaded('documents', fn () => $this->documents->values(), collect());

        return [
            'id' => $this->id,
            'owner_id' => $this->owner_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'body' => $this->body,
            'is_pinned' => (bool) $this->is_pinned,
            'visibility' => $this->visibility,
            'publish_at' => optional($this->publish_at)?->toISOString(),

            'cover_url' => $this->cover_url, // مثل کمپین

            'covers' => $covers->map(function ($m) {
                return [
                    'id' => $m->id,
                    'url' => $m->publicUrl(),
                    'order' => $m->pivot->order_column ?? 0,
                ];
            }),
            'documents' => $documents->map(function ($m) {
                return [
                    'id' => $m->id,
                    'url' => $m->publicUrl(),
                    'order' => $m->pivot->order_column ?? 0,
                ];
            }),

            'created_at' => optional($this->created_at)?->toISOString(),
            'updated_at' => optional($this->updated_at)?->toISOString(),
        ];
    }
}
