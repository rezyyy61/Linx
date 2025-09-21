<?php

declare(strict_types=1);

namespace App\Services\Event;

use App\Models\Event\Event;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PublicEventService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $q = Event::query()
            ->with(['covers', 'documents', 'settings', 'organizer'])
            ->where('is_published', true)
            ->whereHas('settings', function ($s) {
                $s->where('visibility', 'public');
            });

        if (! empty($filters['q'])) {
            $term = trim((string) $filters['q']);
            $q->where(function ($qq) use ($term) {
                $qq->where('title', 'like', '%'.$term.'%')
                    ->orWhere('description', 'like', '%'.$term.'%');
            });
        }

        if (! empty($filters['starts_from'])) {
            $q->where('starts_at', '>=', $filters['starts_from']);
        }
        if (! empty($filters['starts_to'])) {
            $q->where('starts_at', '<=', $filters['starts_to']);
        }

        $allowedOrder = ['starts_at', 'created_at', 'updated_at'];
        $orderBy = in_array($filters['order_by'] ?? '', $allowedOrder, true) ? $filters['order_by'] : 'starts_at';
        $orderDir = in_array($filters['order_dir'] ?? '', ['asc', 'desc'], true) ? $filters['order_dir'] : 'asc';

        $perPage = (int) ($filters['per_page'] ?? 15);
        if ($perPage < 1) {
            $perPage = 1;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

        return $q->orderBy($orderBy, $orderDir)->paginate($perPage);
    }

    public function findBySlug(string $slug): ?Event
    {
        $event = Event::query()
            ->with(['covers', 'documents', 'settings', 'organizer'])
            ->where('is_published', true)
            ->where('slug', $slug)
            ->first();

        if (! $event) {
            return null;
        }

        $visibility = $event->settings->visibility ?? 'public';
        if ($visibility === 'private') {
            return null;
        }

        return $event;
    }
}
