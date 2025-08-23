<?php

namespace App\Http\Resources\Profile;

use Illuminate\Http\Resources\Json\JsonResource;

class ProfileTranslationResource extends JsonResource
{
    public function toArray($request): array
    {
        $r = $this->resource;
        $createdAt = $r->getAttribute('created_at');
        $updatedAt = $r->getAttribute('updated_at');

        return [
            'id' => $r->getAttribute('id'),
            'profile_id' => $r->getAttribute('profile_id'),
            'locale' => $r->getAttribute('locale'),
            'tagline' => $r->getAttribute('tagline'),
            'about' => $r->getAttribute('about'),
            'goals' => $r->getAttribute('goals'),
            'activities' => $r->getAttribute('activities'),
            'structure' => $r->getAttribute('structure'),
            'created_at' => $createdAt ? $createdAt->toIso8601String() : null,
            'updated_at' => $updatedAt ? $updatedAt->toIso8601String() : null,
        ];
    }
}
