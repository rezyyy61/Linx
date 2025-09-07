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

            $mediaPayload = Arr::only($data, ['covers', 'documents', 'cover_id']);
            $payload = Arr::except($data, ['covers', 'documents', 'cover_id']);

            $payload = $this->ensureSlug($payload, null);
            $payload = $this->normalize($payload);

            $announcement = Announcement::create($payload);

            if (! empty($mediaPayload)) {
                $this->syncMedia($announcement, $mediaPayload);
            }

            return (int) $announcement->id;
        });
    }

    public function update(Announcement $announcement, array $data): Announcement
    {
        return DB::transaction(function () use ($announcement, $data) {
            $mediaPayload = Arr::only($data, ['covers', 'documents', 'cover_id']);
            $payload = Arr::except($data, ['covers', 'documents', 'cover_id']);

            $payload = $this->ensureSlug($payload, $announcement->id);
            $payload = $this->normalize($payload);

            $announcement->fill($payload)->save();

            if (! empty($mediaPayload)) {
                $this->syncMedia($announcement, $mediaPayload);
            }

            return $announcement->refresh();
        });
    }

    public function delete(Announcement $announcement): void
    {
        $announcement->delete();
    }

    protected function syncMedia(Announcement $a, array $payload): void
    {
        if (array_key_exists('covers', $payload)) {
            $a->media()->wherePivot('collection', 'announcement-cover')->detach();
            foreach (array_values($payload['covers'] ?? []) as $i => $row) {
                $media = Media::findOrFail((int) $row['id']);
                $order = (int) ($row['order'] ?? $i);
                $this->mediaService->attachMedia($a, $media, 'announcement-cover', $order);
            }
        }

        if (! empty($payload['cover_id'])) {
            $media = Media::findOrFail((int) $payload['cover_id']);
            $this->mediaService->replaceSingle($a, $media, 'announcement-cover', 0);
        }

        if (array_key_exists('documents', $payload)) {
            $a->media()->wherePivot('collection', 'announcement-document')->detach();
            foreach (array_values($payload['documents'] ?? []) as $i => $row) {
                $media = Media::findOrFail((int) $row['id']);
                $order = (int) ($row['order'] ?? $i);
                $this->mediaService->attachMedia($a, $media, 'announcement-document', $order);
            }
        }
    }
}
