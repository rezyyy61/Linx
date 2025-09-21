<?php

namespace App\Http\Resources\Event;

use App\Http\Resources\PublicApi\PublicMiniUserResource;
use App\Models\Event\Event;
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

            'documents' => $this->whenLoaded('documents', function () {
                return $this->documents->map(static function ($m) {
                    return [
                        'id' => (int) data_get($m, 'id'),
                        'url' => method_exists($m, 'publicUrl') ? $m->publicUrl() : null,
                    ];
                })->values()->all();
            }),

            'organizer' => $this->whenLoaded('organizer', function () {
                return new PublicMiniUserResource($this->organizer);
            }),

            'created_at' => optional($this->created_at)?->toISOString(),
            'updated_at' => optional($this->updated_at)?->toISOString(),
        ];
    }
}
