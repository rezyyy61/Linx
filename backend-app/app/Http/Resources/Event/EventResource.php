<?php

namespace App\Http\Resources\Event;

use App\Models\Event\Event;
use App\Models\Media;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Event
 */
class EventResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => (int) $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'slug' => $this->slug,
            'timezone' => $this->timezone,

            'starts_at' => optional($this->starts_at)?->toISOString(),
            'ends_at' => optional($this->ends_at)?->toISOString(),

            'starts_at_local' => $this->starts_at_local,
            'ends_at_local' => $this->ends_at_local,

            'location' => $this->location,
            'capacity' => $this->capacity,
            'is_published' => (bool) $this->is_published,
            'publish_at' => optional($this->publish_at)?->toISOString(),
            'cover_url' => $this->cover_url,

            'settings' => $this->whenLoaded('settings', function () {
                $s = $this->settings;

                return [
                    'type' => $s->type,
                    'visibility' => $s->visibility,
                    'join_url' => $s->join_url,
                    'join_platform' => $s->join_platform,
                    'join_passcode' => $s->join_passcode,
                    'join_instructions' => $s->join_instructions,
                    'join_visible_minutes_before' => $s->join_visible_minutes_before,
                    'access_code' => $s->access_code,
                    'og_title' => $s->og_title,
                    'og_description' => $s->og_description,
                ];
            }, null),

            'documents' => $this->whenLoaded('documents', function () {
                $docs = $this->documents;

                return $docs->map(static function (Media $m) {
                    return [
                        'id' => (int) $m->id,
                        'url' => $m->publicUrl(),
                    ];
                })->values()->all();
            }),

            'created_at' => optional($this->created_at)?->toISOString(),
            'updated_at' => optional($this->updated_at)?->toISOString(),
        ];
    }
}
