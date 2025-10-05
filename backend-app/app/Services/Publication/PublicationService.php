<?php

declare(strict_types=1);

namespace App\Services\Publication;

use App\Models\Media;
use App\Models\Publication\Publication;
use App\Services\Media\MediaService;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PublicationService
{
    public const COVER_COLLECTION = 'publication-cover';

    public const DOCS_COLLECTION = 'publication-document';

    public const DOCS_MAX = 100;

    public function __construct(private MediaService $mediaService) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        $q = Publication::query()->with(['covers', 'documents', 'owner']);

        if (auth()->check()) {
            $q->where('owner_id', auth()->id());
        }

        if (! empty($filters['q'])) {
            $term = (string) $filters['q'];
            $q->where(function ($qq) use ($term) {
                $qq->where('title', 'like', '%'.$term.'%')
                    ->orWhere('issue', 'like', '%'.$term.'%')
                    ->orWhere('description', 'like', '%'.$term.'%');
            });
        }

        if (array_key_exists('published', $filters)) {
            $q->where('is_published', (bool) $filters['published']);
        }

        if (! empty($filters['published_from'])) {
            $q->where('publish_at', '>=', $filters['published_from']);
        }
        if (! empty($filters['published_to'])) {
            $q->where('publish_at', '<=', $filters['published_to']);
        }

        $allowedOrderBy = ['publish_at', 'created_at', 'updated_at'];
        $orderByInput = (string) ($filters['order_by'] ?? 'publish_at');
        $orderBy = in_array($orderByInput, $allowedOrderBy, true) ? $orderByInput : 'publish_at';

        $orderDirInput = strtolower((string) ($filters['order_dir'] ?? 'desc'));
        $orderDir = in_array($orderDirInput, ['asc', 'desc'], true) ? $orderDirInput : 'desc';

        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = max(1, min(100, $perPage));

        return $q->orderBy($orderBy, $orderDir)->paginate($perPage);
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
            $data['slug'] = Publication::uniqueSlug($data['slug'], $ignoreId);

            return $data;
        }
        if (! empty($data['title'])) {
            $data['slug'] = Publication::uniqueSlug($data['title'], $ignoreId);
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

            $publication = Publication::create($payload);

            $this->syncCover($publication, $mediaPayload['cover_id'] ?? null);
            $this->syncDocuments($publication, $mediaPayload['documents'] ?? null);

            return (int) $publication->id;
        });
    }

    public function update(Publication $publication, array $data): Publication
    {
        return DB::transaction(function () use ($publication, $data) {
            $mediaPayload = Arr::only($data, ['documents', 'cover_id']);
            $payload = Arr::except($data, ['documents', 'cover_id']);

            $payload = $this->ensureSlug($payload, $publication->id);
            $payload = $this->normalize($payload);

            $publication->fill($payload)->save();

            $this->syncCover($publication, $mediaPayload['cover_id'] ?? null);
            $this->syncDocuments($publication, $mediaPayload['documents'] ?? null);

            return $publication->refresh();
        });
    }

    public function delete(Publication $publication): void
    {
        $detachedIds = [];

        DB::transaction(function () use ($publication, &$detachedIds) {
            $ids = $publication->media()->pluck('media.id')->all();
            $detachedIds = array_map('intval', $ids);

            if ($detachedIds) {
                $publication->media()->detach($detachedIds);
            }

            $publication->delete();
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

    protected function syncCover(Publication $p, $coverId): void
    {
        if ($coverId === null || $coverId === '') {
            $detached = $p->media()
                ->wherePivot('collection', self::COVER_COLLECTION)
                ->pluck('media.id')->all();

            $p->media()->wherePivot('collection', self::COVER_COLLECTION)->detach();

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
            $this->syncCover($p, null);

            return;
        }

        $this->mediaService->replaceSingle($p, $media, self::COVER_COLLECTION, 0);
    }

    protected function syncDocuments(Publication $p, ?array $items): void
    {
        if ($items === null) {
            return;
        }

        if ($items === []) {
            $detachedIds = $p->media()
                ->wherePivot('collection', self::DOCS_COLLECTION)
                ->pluck('media.id')->all();

            $p->media()->wherePivot('collection', self::DOCS_COLLECTION)->detach();

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
            $this->syncDocuments($p, []);

            return;
        }

        $existingIds = Media::withTrashed()
            ->whereIn('id', array_keys($prepared))
            ->pluck('id')->map(fn ($i) => (int) $i)->all();

        if ($existingIds === []) {
            $this->syncDocuments($p, []);

            return;
        }

        $currentIds = $p->media()
            ->wherePivot('collection', self::DOCS_COLLECTION)
            ->pluck('media.id')->map(fn ($i) => (int) $i)->all();

        $toDetach = array_diff($currentIds, $existingIds);
        $p->media()->wherePivot('collection', self::DOCS_COLLECTION)->detach($toDetach);

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

        $p->media()->syncWithoutDetaching($syncPayload);

        foreach ($syncPayload as $id => $payload) {
            DB::table('mediables')
                ->where('mediable_type', Publication::class)
                ->where('mediable_id', $p->id)
                ->where('media_id', $id)
                ->where('collection', self::DOCS_COLLECTION)
                ->update(['order_column' => $payload['order_column'], 'updated_at' => now()]);
        }
    }
}
