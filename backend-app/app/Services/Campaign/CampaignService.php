<?php

declare(strict_types=1);

namespace App\Services\Campaign;

use App\Models\Campaign\Campaign;
use App\Models\Media;
use App\Services\Media\MediaService;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CampaignService
{
    public function __construct(private MediaService $mediaService) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        $q = Campaign::query()->with(['covers', 'documents']);

        if (auth()->check()) {
            $q->where('owner_id', auth()->id());
        }

        if (! empty($filters['q'])) {
            $q->where('title', 'like', '%'.$filters['q'].'%');
        }

        if (array_key_exists('status', $filters) && $filters['status'] !== null && $filters['status'] !== '') {
            $q->where('status', (string) $filters['status']);
        }

        if (! empty($filters['starts_from'])) {
            $q->where('starts_at', '>=', $filters['starts_from']);
        }
        if (! empty($filters['starts_to'])) {
            $q->where('starts_at', '<=', $filters['starts_to']);
        }

        $allowedOrderBy = ['starts_at', 'created_at', 'updated_at'];

        $orderByInput = (string) ($filters['order_by'] ?? '');
        $orderBy = in_array($orderByInput, $allowedOrderBy, true) ? $orderByInput : 'starts_at';

        $orderDirInput = strtolower((string) ($filters['order_dir'] ?? 'desc'));
        $orderDir = in_array($orderDirInput, ['asc', 'desc'], true) ? $orderDirInput : 'desc';

        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = max(1, min(100, $perPage));

        return $q->orderBy($orderBy, $orderDir)->paginate($perPage);
    }

    protected function normalizeDatetimes(array $data): array
    {
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
            $data[$k] = $hasOffset ? Carbon::parse($s)->setTimezone('UTC') : Carbon::parse($s, config('app.timezone', 'Europe/Amsterdam'))->setTimezone('UTC');
        }

        return $data;
    }

    protected function ensureSlug(array $data, ?int $ignoreId = null): array
    {
        if (! empty($data['slug'])) {
            $data['slug'] = Campaign::uniqueSlug($data['slug'], $ignoreId);

            return $data;
        }
        if (! empty($data['title'])) {
            $data['slug'] = Campaign::uniqueSlug($data['title'], $ignoreId);
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
            $payload = $this->normalizeDatetimes($payload);

            $campaign = Campaign::create($payload);

            if (! empty($mediaPayload)) {
                $this->syncMedia($campaign, $mediaPayload);
            }

            return (int) $campaign->id;
        });
    }

    public function update(Campaign $campaign, array $data): Campaign
    {
        return DB::transaction(function () use ($campaign, $data) {
            $mediaPayload = Arr::only($data, ['covers', 'documents', 'cover_id']);
            $payload = Arr::except($data, ['covers', 'documents', 'cover_id']);

            $payload = $this->ensureSlug($payload, $campaign->id);
            $payload = $this->normalizeDatetimes($payload);

            $campaign->fill($payload)->save();

            if (! empty($mediaPayload)) {
                $this->syncMedia($campaign, $mediaPayload);
            }

            return $campaign->refresh();
        });
    }

    public function delete(Campaign $campaign): void
    {
        $campaign->delete();
    }

    protected function syncMedia(Campaign $campaign, array $payload): void
    {
        if (array_key_exists('covers', $payload)) {
            $campaign->media()->wherePivot('collection', 'campaign-cover')->detach();
            foreach (array_values($payload['covers'] ?? []) as $i => $row) {
                $media = Media::findOrFail((int) $row['id']);
                $order = (int) ($row['order'] ?? $i);
                $this->mediaService->attachMedia($campaign, $media, 'campaign-cover', $order);
            }
        }

        if (! empty($payload['cover_id'])) {
            $media = Media::findOrFail((int) $payload['cover_id']);
            $this->mediaService->replaceSingle($campaign, $media, 'campaign-cover', 0);
        }

        if (array_key_exists('documents', $payload)) {
            $campaign->media()->wherePivot('collection', 'campaign-document')->detach();
            foreach (array_values($payload['documents'] ?? []) as $i => $row) {
                $media = Media::findOrFail((int) $row['id']);
                $order = (int) ($row['order'] ?? $i);
                $this->mediaService->attachMedia($campaign, $media, 'campaign-document', $order);
            }
        }
    }
}
