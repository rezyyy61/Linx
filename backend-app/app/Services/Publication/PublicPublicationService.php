<?php

declare(strict_types=1);

namespace App\Services\Publication;

use App\Models\Publication\Publication;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PublicPublicationService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $now = Carbon::now();

        $q = Publication::query()
            ->with(['covers', 'documents', 'owner'])
            ->where('is_published', true)
            ->where(function (Builder $w) use ($now) {
                $w->whereNull('publish_at')->orWhere('publish_at', '<=', $now);
            });

        if (! empty($filters['q'])) {
            $term = trim((string) $filters['q']);
            $q->where(function (Builder $qq) use ($term) {
                $qq->where('title', 'like', "%{$term}%")
                    ->orWhere('issue', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        if (! empty($filters['language'])) {
            $q->where('language', $filters['language']);
        }

        $tsCol = DB::raw('COALESCE(publish_at, created_at)');

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
                $q->whereBetween($tsCol, [$start, $end]);
            }
        } else {
            if (! empty($filters['published_from'])) {
                $q->where($tsCol, '>=', $filters['published_from']);
            }
            if (! empty($filters['published_to'])) {
                $q->where($tsCol, '<=', $filters['published_to']);
            }
        }

        $allowedOrder = ['publish_at', 'created_at', 'updated_at'];
        $orderBy = in_array($filters['order_by'] ?? '', $allowedOrder, true) ? $filters['order_by'] : 'publish_at';
        $orderDir = in_array($filters['order_dir'] ?? '', ['asc', 'desc'], true) ? $filters['order_dir'] : 'desc';

        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = max(1, min(100, $perPage));

        return $q->orderBy($orderBy, $orderDir)->paginate($perPage);
    }

    public function findBySlug(string $slug): ?Publication
    {
        $now = Carbon::now();

        $p = Publication::query()
            ->with(['covers', 'documents', 'owner'])
            ->where('slug', $slug)
            ->first();

        if (! $p) {
            return null;
        }
        if ($p->is_published !== true) {
            return null;
        }
        if ($p->publish_at && $p->publish_at->isFuture()) {
            return null;
        }

        return $p;
    }
}
