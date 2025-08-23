<?php

namespace App\Http\Resources\Profile;

use Illuminate\Http\Resources\Json\JsonResource;

class ProfileLinkResource extends JsonResource
{
    public function toArray($request): array
    {
        $r = $this->resource;
        $createdAt = $r->getAttribute('created_at');
        $updatedAt = $r->getAttribute('updated_at');

        return [
            'id' => $r->getAttribute('id'),
            'profile_id' => $r->getAttribute('profile_id'),
            'type' => $r->getAttribute('type'),
            'title' => $r->getAttribute('title'),
            'value' => $r->getAttribute('value'),
            'url' => $r->getAttribute('url'),
            'order' => $r->getAttribute('order'),
            'created_at' => $createdAt ? $createdAt->toIso8601String() : null,
            'updated_at' => $updatedAt ? $updatedAt->toIso8601String() : null,
        ];
    }
}
