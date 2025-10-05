<?php

declare(strict_types=1);

namespace App\Services\Event;

use App\Models\Event\Event;
use Carbon\Carbon;
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

        $startsFrom = $filters['starts_from'] ?? null;
        $startsTo = $filters['starts_to'] ?? null;

        if (empty($startsFrom) && empty($startsTo) && ! empty($filters['date_range'])) {
            $range = $filters['date_range'];
            $now = Carbon::now();
            if ($range === 'today') {
                $startsFrom = $now->copy()->startOfDay()->toDateTimeString();
                $startsTo = $now->copy()->endOfDay()->toDateTimeString();
            } elseif ($range === 'this_week') {
                $startsFrom = $now->copy()->startOfWeek()->toDateTimeString();
                $startsTo = $now->copy()->endOfWeek()->toDateTimeString();
            } elseif ($range === 'this_month') {
                $startsFrom = $now->copy()->startOfMonth()->toDateTimeString();
                $startsTo = $now->copy()->endOfMonth()->toDateTimeString();
            }
        }

        if (! empty($startsFrom)) {
            $q->where('starts_at', '>=', $startsFrom);
        }
        if (! empty($startsTo)) {
            $q->where('starts_at', '<=', $startsTo);
        }

        if (! empty($filters['status']) && in_array($filters['status'], ['upcoming', 'ongoing', 'past', 'all'], true)) {
            $now = Carbon::now();
            if ($filters['status'] === 'upcoming') {
                $q->where('starts_at', '>', $now);
            } elseif ($filters['status'] === 'ongoing') {
                $q->where('starts_at', '<=', $now)
                    ->where(function ($qq) use ($now) {
                        $qq->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
                    });
            } elseif ($filters['status'] === 'past') {
                $q->where(function ($qq) use ($now) {
                    $qq->whereNotNull('ends_at')->where('ends_at', '<', $now)
                        ->orWhere(function ($q2) use ($now) {
                            $q2->whereNull('ends_at')->where('starts_at', '<', $now);
                        });
                });
            }
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
