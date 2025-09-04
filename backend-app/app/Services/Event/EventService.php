<?php

declare(strict_types=1);

namespace App\Services\Event;

use App\Models\Event\Event;
use App\Models\Event\EventSettings;
use App\Models\Media;
use App\Services\Media\MediaService;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class EventService
{
    public function __construct(private MediaService $mediaService) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        $q = Event::query()->with(['covers', 'documents']);

        if (auth()->check()) {
            $q->where('organizer_id', auth()->id());
        }

        if (! empty($filters['q'])) {
            $q->where('title', 'like', '%'.$filters['q'].'%');
        }

        if (\array_key_exists('is_published', $filters)) {
            $q->where('is_published', (bool) $filters['is_published']);
        }

        if (! empty($filters['starts_from'])) {
            $q->where('starts_at', '>=', $filters['starts_from']);
        }
        if (! empty($filters['starts_to'])) {
            $q->where('starts_at', '<=', $filters['starts_to']);
        }

        $allowedOrderBy = ['starts_at', 'created_at', 'updated_at'];
        $orderBy = \in_array($filters['order_by'] ?? '', $allowedOrderBy, true)
            ? $filters['order_by']
            : 'starts_at';

        $orderDir = \in_array(($filters['order_dir'] ?? 'desc'), ['asc', 'desc'], true)
            ? $filters['order_dir']
            : 'desc';

        $perPage = (int) ($filters['per_page'] ?? 15);
        if ($perPage < 1) {
            $perPage = 1;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

        return $q->orderBy($orderBy, $orderDir)->paginate($perPage);
    }

    protected function normalizeDatetimes(array $data): array
    {
        $tz = $data['timezone'] ?? config('app.timezone', 'Europe/Amsterdam');

        foreach (['starts_at', 'ends_at'] as $k) {
            if (empty($data[$k])) {
                continue;
            }
            $raw = $data[$k];
            if ($raw instanceof \DateTimeInterface) {
                $c = Carbon::instance($raw)->setTimezone('UTC');
                $data[$k] = $c;

                continue;
            }
            $s = (string) $raw;
            $hasOffset = (bool) preg_match('/(Z|[+\-]\d{2}:\d{2})$/', $s);
            $c = $hasOffset
                ? Carbon::parse($s)->setTimezone('UTC')
                : Carbon::parse($s, $tz)->setTimezone('UTC');
            $data[$k] = $c;
        }

        return $data;
    }

    protected function ensureSlug(array $data, ?int $ignoreId = null): array
    {
        if (! empty($data['slug'])) {
            $data['slug'] = Event::uniqueSlug($data['slug'], $ignoreId);

            return $data;
        }
        if (! empty($data['title'])) {
            $data['slug'] = Event::uniqueSlug($data['title'], $ignoreId);
        }

        return $data;
    }

    protected function normalizeSettings(array $s): array
    {
        $out = [];
        if (isset($s['type'])) {
            $out['type'] = in_array($s['type'], ['in_person', 'online', 'hybrid'], true) ? $s['type'] : 'in_person';
        }
        if (isset($s['visibility'])) {
            $out['visibility'] = in_array($s['visibility'], ['public', 'unlisted', 'private'], true) ? $s['visibility'] : 'public';
        }
        foreach (['join_url', 'join_platform', 'join_passcode', 'join_instructions', 'access_code', 'og_title', 'og_description'] as $k) {
            if (array_key_exists($k, $s)) {
                $out[$k] = $s[$k];
            }
        }
        if (array_key_exists('join_visible_minutes_before', $s)) {
            $v = (int) $s['join_visible_minutes_before'];
            if ($v < 0) {
                $v = 0;
            }
            if ($v > 10080) {
                $v = 10080;
            }
            $out['join_visible_minutes_before'] = $v;
        }

        return $out;
    }

    public function create(array $data): int
    {
        return DB::transaction(function () use ($data) {
            if (! isset($data['organizer_id']) && auth()->check()) {
                $data['organizer_id'] = auth()->id();
            }

            $mediaPayload = Arr::only($data, ['covers', 'documents', 'cover_id']);
            $settingsPayload = Arr::pull($data, 'settings', []);
            $eventPayload = Arr::except($data, ['covers', 'documents', 'cover_id']);

            $eventPayload = $this->ensureSlug($eventPayload, null);
            $eventPayload = $this->normalizeDatetimes($eventPayload);

            $event = Event::create($eventPayload);

            if (! empty($settingsPayload)) {
                EventSettings::updateOrCreate(
                    ['event_id' => $event->id],
                    $this->normalizeSettings($settingsPayload) + ['event_id' => $event->id]
                );
            }

            if (! empty($mediaPayload)) {
                $this->syncMedia($event, $mediaPayload);
            }

            return (int) $event->id;
        });
    }

    public function update(Event $event, array $data): Event
    {
        return DB::transaction(function () use ($event, $data) {
            $mediaPayload = Arr::only($data, ['covers', 'documents', 'cover_id']);
            $settingsPayload = Arr::pull($data, 'settings', []);
            $eventPayload = Arr::except($data, ['covers', 'documents', 'cover_id']);

            $eventPayload = $this->ensureSlug($eventPayload, $event->id);
            $eventPayload = $this->normalizeDatetimes($eventPayload);

            $event->fill($eventPayload)->save();

            if (! empty($settingsPayload)) {
                EventSettings::updateOrCreate(
                    ['event_id' => $event->id],
                    $this->normalizeSettings($settingsPayload) + ['event_id' => $event->id]
                );
            }

            if (! empty($mediaPayload)) {
                $this->syncMedia($event, $mediaPayload);
            }

            return $event->refresh();
        });
    }

    public function delete(Event $event): void
    {
        $event->delete();
    }

    protected function syncMedia(Event $event, array $payload): void
    {
        if (array_key_exists('covers', $payload)) {
            $event->media()->wherePivot('collection', 'event-cover')->detach();
            foreach (array_values($payload['covers'] ?? []) as $i => $row) {
                $media = Media::findOrFail((int) $row['id']);
                $order = (int) ($row['order'] ?? $i);
                $this->mediaService->attachMedia($event, $media, 'event-cover', $order);
            }
        }

        if (! empty($payload['cover_id'])) {
            $media = Media::findOrFail((int) $payload['cover_id']);
            $this->mediaService->replaceSingle($event, $media, 'event-cover', 0);
        }

        if (array_key_exists('documents', $payload)) {
            $event->media()->wherePivot('collection', 'event-document')->detach();
            foreach (array_values($payload['documents'] ?? []) as $i => $row) {
                $media = Media::findOrFail((int) $row['id']);
                $order = (int) ($row['order'] ?? $i);
                $this->mediaService->attachMedia($event, $media, 'event-document', $order);
            }
        }

    }
}
