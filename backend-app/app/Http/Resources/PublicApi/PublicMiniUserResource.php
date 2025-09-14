<?php

declare(strict_types=1);

namespace App\Http\Resources\PublicApi;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicMiniUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this->resource;
        $profile = $user->profile;
        $translations = $profile && $profile->relationLoaded('translations') ? $profile->translations : collect();
        $tr = $translations->firstWhere('locale', app()->getLocale()) ?: $translations->first();
        $name = ($tr->name ?? $tr->title ?? null) ?? $profile->name ?? $user->name;

        $logoMedia = null;
        if ($profile) {
            if ($profile->relationLoaded('logo')) {
                $logoMedia = $profile->logo->first();
            } elseif ($profile->relationLoaded('media')) {
                $logoMedia = $profile->media->first(fn ($m) => ($m->pivot->collection ?? null) === 'logo');
            } else {
                $logoMedia = $profile->logo()->first();
            }
        }

        $avatar = $logoMedia instanceof Media ? $logoMedia->publicUrl() : null;

        return [
            'id' => $user->id,
            'name' => $name,
            'slug' => $profile->slug ?? null,
            'avatar' => $avatar,
            'avatarColor' => $profile->avatar_color ?? null,
        ];
    }
}
