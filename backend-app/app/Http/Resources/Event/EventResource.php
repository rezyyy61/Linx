<?php

declare(strict_types=1);

namespace App\Http\Resources\Event;

use App\Http\Resources\PublicApi\PublicMiniUserResource;
use App\Models\Event\Event;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Event
 */
class EventResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var Event $event */
        $event = $this->resource;

        return [
            'id' => (int) $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'slug' => $event->slug,
            'timezone' => $event->timezone,
            'starts_at' => optional($event->starts_at)?->toISOString(),
            'ends_at' => optional($event->ends_at)?->toISOString(),
            'starts_at_local' => $event->starts_at_local,
            'ends_at_local' => $event->ends_at_local,
            'location' => $event->location,
            'capacity' => $event->capacity,
            'is_published' => (bool) $event->is_published,
            'publish_at' => optional($event->publish_at)?->toISOString(),
            'cover_url' => $this->whenLoaded('covers', function () use ($event) {
                $m = $event->covers->sortBy('pivot.order_column')->first();

                return $m && method_exists($m, 'publicUrl') ? $m->publicUrl() : null;
            }),
            'cover_id' => $this->whenLoaded('covers', function () use ($event) {
                $id = $event->covers->sortBy('pivot.order_column')->pluck('id')->first();

                return $id !== null ? (int) $id : null;
            }, null),
            'documents' => $this->whenLoaded('documents', function () use ($event) {
                return $event->documents->map(static function ($m) {
                    /** @var \App\Models\Media $m */
                    return [
                        'id' => (int) $m->id,
                        'url' => method_exists($m, 'publicUrl') ? $m->publicUrl() : null,
                    ];
                })->values()->all();
            }),
            'document_ids' => $this->whenLoaded('documents', function () use ($event) {
                return $event->documents->pluck('id')->map(fn ($i) => (int) $i)->values()->all();
            }, []),
            'settings' => $this->whenLoaded('settings', function () use ($event) {
                $s = $event->settings;

                return [
                    'type' => data_get($s, 'type'),
                    'visibility' => data_get($s, 'visibility'),
                    'join_url' => data_get($s, 'join_url'),
                    'join_platform' => data_get($s, 'join_platform'),
                    'join_passcode' => data_get($s, 'join_passcode'),
                    'join_instructions' => data_get($s, 'join_instructions'),
                    'join_visible_minutes_before' => data_get($s, 'join_visible_minutes_before'),
                    'access_code' => data_get($s, 'access_code'),
                    'og_title' => data_get($s, 'og_title'),
                    'og_description' => data_get($s, 'og_description'),
                ];
            }, null),
            'organizer' => $this->whenLoaded('organizer', function () use ($event) {
                return new PublicMiniUserResource($event->organizer);
            }),
            'created_at' => optional($event->created_at)?->toISOString(),
            'updated_at' => optional($event->updated_at)?->toISOString(),
        ];
    }
}
