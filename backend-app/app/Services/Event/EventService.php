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
    public const COVER_COLLECTION = 'event-cover';

    public const DOCS_COLLECTION = 'event-document';

    public const DOCS_MAX = 100;

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
            ? $filters['order_by'] : 'starts_at';

        $orderDir = \in_array(($filters['order_dir'] ?? 'desc'), ['asc', 'desc'], true)
            ? $filters['order_dir'] : 'desc';

        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = max(1, min(100, $perPage));

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
                $data[$k] = Carbon::instance($raw)->setTimezone('UTC');

                continue;
            }
            $s = (string) $raw;
            $hasOffset = (bool) preg_match('/(Z|[+\-]\d{2}:\d{2})$/', $s);
            $data[$k] = $hasOffset
                ? Carbon::parse($s)->setTimezone('UTC')
                : Carbon::parse($s, $tz)->setTimezone('UTC');
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
            $out['join_visible_minutes_before'] = max(0, min(10080, $v));
        }

        return $out;
    }

    public function create(array $data): int
    {
        return DB::transaction(function () use ($data) {
            if (! isset($data['organizer_id']) && auth()->check()) {
                $data['organizer_id'] = auth()->id();
            }

            $mediaPayload = Arr::only($data, ['documents', 'cover_id']);
            $settingsPayload = Arr::pull($data, 'settings', []);
            $eventPayload = Arr::except($data, ['documents', 'cover_id']);

            $eventPayload = $this->ensureSlug($eventPayload, null);
            $eventPayload = $this->normalizeDatetimes($eventPayload);

            $event = Event::create($eventPayload);

            if (! empty($settingsPayload)) {
                EventSettings::updateOrCreate(
                    ['event_id' => $event->id],
                    $this->normalizeSettings($settingsPayload) + ['event_id' => $event->id]
                );
            }

            $this->syncCover($event, $mediaPayload['cover_id'] ?? null);
            $this->syncDocuments($event, $mediaPayload['documents'] ?? null);

            return (int) $event->id;
        });
    }

    public function update(Event $event, array $data): Event
    {
        return DB::transaction(function () use ($event, $data) {
            $mediaPayload = Arr::only($data, ['documents', 'cover_id']);
            $settingsPayload = Arr::pull($data, 'settings', []);
            $eventPayload = Arr::except($data, ['documents', 'cover_id']);

            if (array_key_exists('organizer_id', $eventPayload) && empty($eventPayload['organizer_id'])) {
                unset($eventPayload['organizer_id']);
            }

            $eventPayload = $this->ensureSlug($eventPayload, $event->id);
            $eventPayload = $this->normalizeDatetimes($eventPayload);

            $event->fill($eventPayload)->save();

            if (! empty($settingsPayload)) {
                EventSettings::updateOrCreate(
                    ['event_id' => $event->id],
                    $this->normalizeSettings($settingsPayload) + ['event_id' => $event->id]
                );
            }

            if (array_key_exists('documents', $mediaPayload)) {
                $this->syncDocuments($event, $mediaPayload['documents']);
            }

            if (array_key_exists('cover_id', $mediaPayload)) {
                $this->syncCover($event, $mediaPayload['cover_id']);
            }

            return $event->refresh();
        });
    }

    public function delete(Event $event): void
    {
        $detachedIds = [];

        DB::transaction(function () use ($event, &$detachedIds) {
            $ids = $event->media()->pluck('media.id')->all();
            $detachedIds = array_map('intval', $ids);

            if ($detachedIds) {
                $event->media()->detach($detachedIds);
            }

            $event->delete();
        });

        if ($detachedIds) {
            DB::afterCommit(function () use ($detachedIds) {
                $medias = Media::withTrashed()->whereIn('id', $detachedIds)->get();
                foreach ($medias as $m) {
                    app(MediaService::class)->deleteIfOrphan($m);
                }
            });
        }
    }

    protected function syncCover(Event $event, $coverId): void
    {
        if ($coverId === null || $coverId === '') {
            $detached = $event->media()
                ->wherePivot('collection', self::COVER_COLLECTION)
                ->pluck('media.id')->all();

            $event->media()->wherePivot('collection', self::COVER_COLLECTION)->detach();

            if ($detached) {
                DB::afterCommit(function () use ($detached) {
                    $items = Media::withTrashed()->whereIn('id', $detached)->get();
                    foreach ($items as $m) {
                        app(MediaService::class)->deleteIfOrphan($m);
                    }
                });
            }

            return;
        }

        $media = Media::withTrashed()->find((int) $coverId);
        if (! $media) {
            $this->syncCover($event, null);

            return;
        }

        $this->mediaService->replaceSingle($event, $media, self::COVER_COLLECTION, 0);
    }

    protected function syncDocuments(Event $event, ?array $items): void
    {
        if ($items === null) {
            return;
        }

        if ($items === []) {
            $detachedIds = $event->media()
                ->wherePivot('collection', self::DOCS_COLLECTION)
                ->pluck('media.id')->all();

            $event->media()->wherePivot('collection', self::DOCS_COLLECTION)->detach();

            if ($detachedIds) {
                DB::afterCommit(function () use ($detachedIds) {
                    $medias = Media::withTrashed()->whereIn('id', $detachedIds)->get();
                    foreach ($medias as $m) {
                        app(MediaService::class)->deleteIfOrphan($m);
                    }
                });
            }

            return;
        }

        $prepared = [];
        foreach ($items as $idx => $row) {
            if (! isset($row['id'])) {
                continue;
            }
            $id = (int) $row['id'];
            $order = isset($row['order']) ? (int) $row['order'] : $idx;
            $prepared[$id] = ['collection' => self::DOCS_COLLECTION, 'order_column' => $order];
            if (count($prepared) >= self::DOCS_MAX) {
                break;
            }
        }

        if ($prepared === []) {
            $this->syncDocuments($event, []);

            return;
        }

        $existingIds = Media::withTrashed()
            ->whereIn('id', array_keys($prepared))
            ->pluck('id')->map(fn ($i) => (int) $i)->all();

        if ($existingIds === []) {
            $this->syncDocuments($event, []);

            return;
        }

        $currentIds = $event->media()
            ->wherePivot('collection', self::DOCS_COLLECTION)
            ->pluck('media.id')->map(fn ($i) => (int) $i)->all();

        $toDetach = array_diff($currentIds, $existingIds);
        $event->media()->wherePivot('collection', self::DOCS_COLLECTION)->detach($toDetach);

        if ($toDetach) {
            DB::afterCommit(function () use ($toDetach) {
                $medias = Media::withTrashed()->whereIn('id', $toDetach)->get();
                foreach ($medias as $m) {
                    app(MediaService::class)->deleteIfOrphan($m);
                }
            });
        }

        $syncPayload = [];
        foreach ($existingIds as $id) {
            $syncPayload[$id] = $prepared[$id];
        }

        $event->media()->syncWithoutDetaching($syncPayload);

        foreach ($syncPayload as $id => $p) {
            DB::table('mediables')
                ->where('mediable_type', Event::class)
                ->where('mediable_id', $event->id)
                ->where('media_id', $id)
                ->where('collection', self::DOCS_COLLECTION)
                ->update(['order_column' => $p['order_column'], 'updated_at' => now()]);
        }
    }
}
