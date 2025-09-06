<?php

namespace App\Http\Resources\Follow;

use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserSummaryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username ?? null,
            'avatar' => $this->avatar_url ?? null,
            'followers_count' => $this->whenCounted('followers'),
            'followings_count' => $this->whenCounted('followings'),
            // هر فیلد امن دیگری که خواستی اضافه کن
        ];
    }
}
