<?php

declare(strict_types=1);

namespace App\Services\Campaign;

use App\Models\Campaign\Campaign;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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

        if (! empty($filters['date_range']) && $filters['date_range'] !== 'all') {
            $start = null;
            $end = $now->copy();
            switch ($filters['date_range']) {
                case 'today':
                    $start = $now->copy()->startOfDay();
                    $end = $now->copy()->endOfDay();
                    break;
                case 'this_week':
                    $start = $now->copy()->startOfWeek();
                    $end = $now->copy()->endOfWeek();
                    break;
                case 'this_month':
                    $start = $now->copy()->startOfMonth();
                    $end = $now->copy()->endOfMonth();
                    break;
            }
            if ($start) {
                $q->whereBetween(DB::raw('COALESCE(publish_at, created_at)'), [$start, $end]);
            }
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

        return Campaign::query()
            ->with(['covers', 'documents', 'owner'])
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->where(function ($qq) use ($now) {
                $qq->whereNull('publish_at')->orWhere('publish_at', '<=', $now);
            })
            ->where('slug', $slug)
            ->first() ?: null;
    }
}
