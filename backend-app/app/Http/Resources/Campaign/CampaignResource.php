<?php

namespace App\Http\Resources\Campaign;

use App\Http\Resources\PublicApi\PublicMiniUserResource;
use App\Models\Campaign\Campaign;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * @property-read Campaign $resource
 */
class CampaignResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        /** @var Campaign $campaign */
        $campaign = $this->resource;

        $meta = is_array($campaign->meta) ? $campaign->meta : (array) $campaign->meta;

        // Media: cover
        /** @var Media|null $cover */
        $cover = $campaign->relationLoaded('covers')
            ? $campaign->covers->first()
            : $campaign->covers()->first();

        // Media: documents
        /** @var Collection<int, Media> $docs */
        $docs = $campaign->relationLoaded('documents')
            ? $campaign->documents
            : $campaign->documents()->get();

        return [
            // Identity
            'id' => (int) $campaign->id,
            'slug' => (string) ($campaign->slug ?? ''),
            'title' => (string) ($campaign->title ?? ''),
            'excerpt' => $campaign->excerpt,
            'description' => $campaign->description,

            // Type / status
            'kind' => (string) ($campaign->kind ?? ''),
            'status' => (string) ($campaign->status ?? ''),
            'visibility' => (string) ($campaign->visibility ?? ''),

            // Dates
            'starts_at' => $campaign->starts_at?->toIso8601String(),
            'ends_at' => $campaign->ends_at?->toIso8601String(),
            'publish_at' => $campaign->publish_at?->toIso8601String(),

            // Media
            'cover_id' => $cover?->id ? (int) $cover->id : null,
            'cover_url' => $cover?->publicUrl(),
            'documents' => $docs->map(
                static function (Media $m): array {
                    return [
                        'id' => (int) $m->id,
                        'url' => $m->publicUrl(),
                    ];
                }
            )->values(),

            // Owner
            'owner_id' => $campaign->owner_id ? (int) $campaign->owner_id : null,
            'owner' => $this->whenLoaded('owner', fn () => new PublicMiniUserResource($campaign->owner)),

            // Meta (both nested and flattened)
            'meta' => [
                'goal_amount' => $meta['goal_amount'] ?? null,
                'goal_currency' => $meta['goal_currency'] ?? null,
                'raised_amount' => $meta['raised_amount'] ?? null,
                'signature_goal' => $meta['signature_goal'] ?? null,
                'signatures_count' => $meta['signatures_count'] ?? null,
                'needed_slots' => $meta['needed_slots'] ?? null,
                'filled_slots' => $meta['filled_slots'] ?? null,
                'target_reach' => $meta['target_reach'] ?? null,
                'current_reach' => $meta['current_reach'] ?? null,
            ],

            'goal_amount' => $meta['goal_amount'] ?? null,
            'goal_currency' => $meta['goal_currency'] ?? null,
            'raised_amount' => $meta['raised_amount'] ?? null,
            'signature_goal' => $meta['signature_goal'] ?? null,
            'signatures_count' => $meta['signatures_count'] ?? null,
            'needed_slots' => $meta['needed_slots'] ?? null,
            'filled_slots' => $meta['filled_slots'] ?? null,
            'target_reach' => $meta['target_reach'] ?? null,
            'current_reach' => $meta['current_reach'] ?? null,

            // Timestamps
            'created_at' => $campaign->created_at?->toIso8601String(),
            'updated_at' => $campaign->updated_at?->toIso8601String(),
        ];
    }
}
