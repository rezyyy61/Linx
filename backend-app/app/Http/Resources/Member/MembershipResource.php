<?php

namespace App\Http\Resources\Member;

use App\Http\Resources\Profile\ProfileLiteResource;
use Illuminate\Http\Resources\Json\JsonResource;

class MembershipResource extends JsonResource
{
    /** @return array<string,mixed> */
    public function toArray($request): array
    {
        /** @var \App\Models\Member\Membership $m */
        $m = $this->resource;

        return [
            'id' => $m->id,
            'owner_id' => $m->owner_id,
            'member_id' => $m->member_id,
            'email' => $m->email,
            'contact_info' => $m->contact_info,
            'meta' => $m->meta,
            'status' => $m->status,
            'consent_at' => $m->consent_at,
            'created_at' => optional($m->created_at)->toISOString(),

            'member' => $this->when(
                $m->relationLoaded('member')
                && $m->member
                && $m->member->relationLoaded('profile'),
                fn () => new ProfileLiteResource($m->member->profile)
            ),

            'owner' => $this->when(
                $m->relationLoaded('owner')
                && $m->owner
                && $m->owner->relationLoaded('profile'),
                fn () => new ProfileLiteResource($m->owner->profile)
            ),
        ];
    }
}
