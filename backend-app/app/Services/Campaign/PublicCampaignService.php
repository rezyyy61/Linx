<?php

declare(strict_types=1);

namespace App\Services\Campaign;

use App\Models\Campaign\Campaign;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class PublicCampaignService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $now = Carbon::now('UTC');

        $q = Campaign::query()
            ->with(['covers', 'documents', 'owner'])
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->where(function ($qq) use ($now) {
                $qq->whereNull('publish_at')->orWhere('publish_at', '<=', $now);
            });

        if (! empty($filters['q'])) {
            $term = trim((string) $filters['q']);
            $q->where(function ($qq) use ($term) {
                $qq->where('title', 'like', "%{$term}%")
                    ->orWhere('excerpt', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        if (! empty($filters['kind']) && in_array($filters['kind'], ['fundraising', 'petition', 'volunteer', 'awareness'], true)) {
            $q->where('kind', $filters['kind']);
        }

        if (! empty($filters['starts_from'])) {
            $q->where('starts_at', '>=', $filters['starts_from']);
        }
        if (! empty($filters['starts_to'])) {
            $q->where('starts_at', '<=', $filters['starts_to']);
        }

        $allowedOrder = ['publish_at', 'starts_at', 'created_at', 'updated_at', 'title'];
        $orderBy = in_array($filters['order_by'] ?? '', $allowedOrder, true) ? $filters['order_by'] : 'publish_at';
        $orderDir = in_array($filters['order_dir'] ?? '', ['asc', 'desc'], true) ? $filters['order_dir'] : 'desc';

        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = max(1, min(100, $perPage));

        return $q->orderBy($orderBy, $orderDir)->paginate($perPage);
    }

    public function findBySlug(string $slug): ?Campaign
    {
        $now = Carbon::now('UTC');

        $campaign = Campaign::query()
            ->with(['covers', 'documents', 'owner'])
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->where(function ($qq) use ($now) {
                $qq->whereNull('publish_at')->orWhere('publish_at', '<=', $now);
            })
            ->where('slug', $slug)
            ->first();

        return $campaign ?: null;
    }
}
