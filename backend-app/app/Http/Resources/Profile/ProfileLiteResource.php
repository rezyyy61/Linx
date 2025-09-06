<?php

namespace App\Http\Resources\Profile;

use App\Models\Media;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Profile\Profile */
class ProfileLiteResource extends JsonResource
{
    public function toArray($request): array
    {
        $locale = app()->getLocale();

        $translations = $this->relationLoaded('translations') ? $this->translations : collect();
        $tr = $translations->firstWhere('locale', $locale) ?: $translations->first();

        $name = ($tr->name ?? $tr->title ?? null)
            ?? $this->getAttribute('name')
            ?? optional($this->user)->name
            ?? optional($this->whenLoaded('user'))->name;

        $logoMedia = null;

        if ($this->relationLoaded('logo')) {
            $logoMedia = $this->logo->first();
        } elseif ($this->relationLoaded('media')) {
            $logoMedia = $this->media->first(fn ($m) => ($m->pivot->collection ?? null) === 'logo');
        } else {
            $logoMedia = $this->logo()->first();
        }

        $avatar = $logoMedia instanceof Media ? $logoMedia->publicUrl() : null;

        return [
            'id' => $this->user_id ?? optional($this->user)->id,
            'name' => $name,
            'slug' => $this->slug,
            'avatar' => $avatar,
            'avatar_color' => $this->avatar_color,
        ];
    }
}
