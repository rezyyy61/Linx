<?php

namespace App\Http\Resources\Member;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentTargetResource extends JsonResource
{
    /** @return array<string,mixed> */
    public function toArray(Request $request): array
    {
        /** @var \App\Models\Member\MemberContentTarget $model */
        $model = $this->resource;

        return [
            'id' => $model->id,
            'content_id' => $model->content_id,
            'membership_id' => $model->membership_id,
            'channel' => $model->channel,
            'status' => $model->status,
            'sent_at' => $model->sent_at,
            'opened_at' => $model->opened_at,
            'clicked_at' => $model->clicked_at,
            'error' => $model->error,
        ];
    }
}
