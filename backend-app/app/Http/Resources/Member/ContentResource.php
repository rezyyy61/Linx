<?php

namespace App\Http\Resources\Member;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentResource extends JsonResource
{
    /** @return array<string,mixed> */
    public function toArray(Request $request): array
    {
        /** @var \App\Models\Member\MemberContent $model */
        $model = $this->resource;

        return [
            'id' => $model->id,
            'owner_id' => $model->owner_id,
            'type' => $model->type,
            'title' => $model->title,
            'body' => $model->body,
            'options' => $model->options,
            'status' => $model->status,
            'scheduled_at' => $model->scheduled_at,
            'sent_at' => $model->sent_at,
            'created_at' => $model->created_at,
        ];
    }
}
