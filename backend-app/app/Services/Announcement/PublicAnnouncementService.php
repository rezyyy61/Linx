<?php

declare(strict_types=1);

namespace App\Services\Announcement;

use App\Models\Announcement\Announcement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class PublicAnnouncementService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $now = Carbon::now();

        $q = Announcement::query()
            ->with(['covers', 'documents', 'owner'])
            ->where('visibility', 'public')
            ->where(function (Builder $w) use ($now) {
                $w->whereNull('publish_at')->orWhere('publish_at', '<=', $now);
            });

        if (! empty($filters['q'])) {
            $term = trim((string) $filters['q']);
            $q->where(function (Builder $qq) use ($term) {
                $qq->where('title', 'like', '%'.$term.'%')
                    ->orWhere('body', 'like', '%'.$term.'%');
            });
        }

        if (! empty($filters['published_from'])) {
            $q->where('publish_at', '>=', $filters['published_from']);
        }
        if (! empty($filters['published_to'])) {
            $q->where('publish_at', '<=', $filters['published_to']);
        }

        if (! empty($filters['only_pinned'])) {
            $q->where('is_pinned', true);
        }

        $allowedOrder = ['publish_at', 'created_at', 'updated_at'];
        $orderBy = in_array($filters['order_by'] ?? '', $allowedOrder, true) ? $filters['order_by'] : 'publish_at';
        $orderDir = in_array($filters['order_dir'] ?? '', ['asc', 'desc'], true) ? $filters['order_dir'] : 'desc';

        $perPage = (int) ($filters['per_page'] ?? 15);
        if ($perPage < 1) {
            $perPage = 1;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

        return $q->orderByDesc('is_pinned')->orderBy($orderBy, $orderDir)->paginate($perPage);
    }

    public function findBySlug(string $slug): ?Announcement
    {
        $now = Carbon::now();

        $a = Announcement::query()
            ->with(['covers', 'documents', 'owner'])
            ->where('slug', $slug)
            ->first();

        if (! $a) {
            return null;
        }
        if ($a->visibility !== 'public') {
            return null;
        }
        if ($a->publish_at && $a->publish_at->isFuture()) {
            return null;
        }

        return $a;
    }
}
