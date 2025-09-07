<?php

namespace App\Http\Resources\Campaign;

use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Campaign\Campaign */
class CampaignResource extends JsonResource
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
            'goal' => $this->goal,
            'description' => $this->description,
            'starts_at' => optional($this->starts_at)?->toISOString(),
            'ends_at' => optional($this->ends_at)?->toISOString(),
            'status' => $this->status,
            'donation_enabled' => (bool) $this->donation_enabled,
            'cover_url' => $this->cover_url,
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
