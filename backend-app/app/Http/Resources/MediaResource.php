<?php

namespace App\Http\Resources;

use App\Enums\MediaStatus;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array{
     *     id: int,
     *     disk: string|null,
     *     key: string,
     *     type: string|null,
     *     status: string|null,
     *     ext: string|null,
     *     size: int|null,
     *     mime: string|null,
     *     url?: string,
     *     created_at: string|null
     * }
     */
    public function toArray($request): array
    {
        /** @var Media $media */
        $media = $this->resource;

        return [
            'id' => $media->id,
            'disk' => $media->disk,
            'key' => $media->key,
            'type' => $media->type?->value,
            'status' => $media->status?->value,
            'ext' => $media->ext,
            'size' => $media->size,
            'mime' => $media->mime,
            'url' => $this->when(
                $media->status === MediaStatus::READY,
                fn () => $media->publicUrl()
            ),
            'created_at' => $media->created_at?->toIso8601String(),
        ];
    }
}
