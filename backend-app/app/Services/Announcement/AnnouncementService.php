<?php

declare(strict_types=1);

namespace App\Services\Announcement;

use App\Models\Announcement\Announcement;
use App\Models\Media;
use App\Services\Media\MediaService;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class AnnouncementService
{
    public const COVER_COLLECTION = 'announcement-cover';

    public const DOCS_COLLECTION = 'announcement-document';

    public const DOCS_MAX = 100;

    public function __construct(private MediaService $mediaService) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        $q = Announcement::query()->with(['covers', 'documents', 'owner']);

        if (auth()->check()) {
            $q->where('owner_id', auth()->id());
        }

        if (! empty($filters['q'])) {
            $q->where('title', 'like', '%'.$filters['q'].'%');
        }

        if (array_key_exists('visibility', $filters) && $filters['visibility']) {
            $q->where('visibility', $filters['visibility']);
        }

        if (array_key_exists('pinned', $filters)) {
            $q->where('is_pinned', (bool) $filters['pinned']);
        }

        if (! empty($filters['published_from'])) {
            $q->where('publish_at', '>=', $filters['published_from']);
        }
        if (! empty($filters['published_to'])) {
            $q->where('publish_at', '<=', $filters['published_to']);
        }

        $allowedOrderBy = ['publish_at', 'created_at', 'updated_at', 'is_pinned'];
        $orderByInput = (string) ($filters['order_by'] ?? '');
        $orderBy = in_array($orderByInput, $allowedOrderBy, true) ? $orderByInput : 'publish_at';

        $orderDirInput = strtolower((string) ($filters['order_dir'] ?? 'desc'));
        $orderDir = in_array($orderDirInput, ['asc', 'desc'], true) ? $orderDirInput : 'desc';

        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = max(1, min(100, $perPage));

        return $q->orderBy('is_pinned', 'desc')->orderBy($orderBy, $orderDir)->paginate($perPage);
    }

    protected function normalize(array $data): array
    {
        if (! empty($data['publish_at'])) {
            $raw = $data['publish_at'];
            if ($raw instanceof \DateTimeInterface) {
                $data['publish_at'] = Carbon::instance($raw)->setTimezone('UTC');
            } else {
                $s = (string) $raw;
                $hasOffset = (bool) preg_match('/(Z|[+\-]\d{2}:\d{2})$/', $s);
                $data['publish_at'] = $hasOffset ? Carbon::parse($s)->setTimezone('UTC') : Carbon::parse($s, config('app.timezone', 'Europe/Amsterdam'))->setTimezone('UTC');
            }
        }

        return $data;
    }

    protected function ensureSlug(array $data, ?int $ignoreId = null): array
    {
        if (! empty($data['slug'])) {
            $data['slug'] = Announcement::uniqueSlug($data['slug'], $ignoreId);

            return $data;
        }
        if (! empty($data['title'])) {
            $data['slug'] = Announcement::uniqueSlug($data['title'], $ignoreId);
        }

        return $data;
    }

    public function create(array $data): int
    {
        return DB::transaction(function () use ($data) {
            if (! isset($data['owner_id']) && auth()->check()) {
                $data['owner_id'] = auth()->id();
            }

            $mediaPayload = Arr::only($data, ['documents', 'cover_id']);
            $payload = Arr::except($data, ['documents', 'cover_id']);

            $payload = $this->ensureSlug($payload, null);
            $payload = $this->normalize($payload);

            $announcement = Announcement::create($payload);

            $this->syncCover($announcement, $mediaPayload['cover_id'] ?? null);
            $this->syncDocuments($announcement, $mediaPayload['documents'] ?? null);

            return (int) $announcement->id;
        });
    }

    public function update(Announcement $announcement, array $data): Announcement
    {
        return DB::transaction(function () use ($announcement, $data) {
            $mediaPayload = Arr::only($data, ['documents', 'cover_id']);
            $payload = Arr::except($data, ['documents', 'cover_id']);

            $payload = $this->ensureSlug($payload, $announcement->id);
            $payload = $this->normalize($payload);

            $announcement->fill($payload)->save();

            $this->syncCover($announcement, $mediaPayload['cover_id'] ?? null);
            $this->syncDocuments($announcement, $mediaPayload['documents'] ?? null);

            return $announcement->refresh();
        });
    }

    public function delete(Announcement $announcement): void
    {
        $detachedIds = [];

        DB::transaction(function () use ($announcement, &$detachedIds) {
            $ids = $announcement->media()->pluck('media.id')->all();
            $detachedIds = array_map('intval', $ids);

            if ($detachedIds) {
                $announcement->media()->detach($detachedIds);
            }

            $announcement->delete();
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

    protected function syncCover(Announcement $a, $coverId): void
    {
        if ($coverId === null || $coverId === '') {
            $detached = $a->media()
                ->wherePivot('collection', self::COVER_COLLECTION)
                ->pluck('media.id')->all();

            $a->media()->wherePivot('collection', self::COVER_COLLECTION)->detach();

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
            $this->syncCover($a, null);

            return;
        }

        $this->mediaService->replaceSingle($a, $media, self::COVER_COLLECTION, 0);
    }

    protected function syncDocuments(Announcement $a, ?array $items): void
    {
        if ($items === null) {
            return;
        }

        if ($items === []) {
            $detachedIds = $a->media()
                ->wherePivot('collection', self::DOCS_COLLECTION)
                ->pluck('media.id')->all();

            $a->media()->wherePivot('collection', self::DOCS_COLLECTION)->detach();

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
            $this->syncDocuments($a, []);

            return;
        }

        $existingIds = Media::withTrashed()
            ->whereIn('id', array_keys($prepared))
            ->pluck('id')->map(fn ($i) => (int) $i)->all();

        if ($existingIds === []) {
            $this->syncDocuments($a, []);

            return;
        }

        $currentIds = $a->media()
            ->wherePivot('collection', self::DOCS_COLLECTION)
            ->pluck('media.id')->map(fn ($i) => (int) $i)->all();

        $toDetach = array_diff($currentIds, $existingIds);
        $a->media()->wherePivot('collection', self::DOCS_COLLECTION)->detach($toDetach);

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

        $a->media()->syncWithoutDetaching($syncPayload);

        foreach ($syncPayload as $id => $p) {
            DB::table('mediables')
                ->where('mediable_type', Announcement::class)
                ->where('mediable_id', $a->id)
                ->where('media_id', $id)
                ->where('collection', self::DOCS_COLLECTION)
                ->update(['order_column' => $p['order_column'], 'updated_at' => now()]);
        }
    }
}
