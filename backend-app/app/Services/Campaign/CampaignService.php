<?php

namespace App\Services\Campaign;

use App\Models\Campaign\Campaign;
use App\Models\Media;
use App\Services\Campaign\DTOs\CreateCampaignData;
use App\Services\Campaign\DTOs\UpdateCampaignData;
use App\Services\Media\MediaService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CampaignService
{
    public const COVER_COLLECTION = 'campaign-cover';

    public const DOCS_COLLECTION = 'campaign-document';

    public const DOCS_MAX = 100;

    public function __construct(private MediaService $mediaService) {}

    public function list(array $filters = [], int $perPage = 15, string $orderBy = 'publish_at', string $orderDir = 'desc'): LengthAwarePaginator
    {
        $q = Campaign::query()->with(['covers', 'documents']);
        $q = CampaignQuery::apply($q, $filters);

        if (! empty($filters['owner_id'])) {
            $q->where('owner_id', (int) $filters['owner_id']);
        }

        $allowedOrderBy = ['publish_at', 'created_at', 'updated_at', 'starts_at', 'ends_at', 'title', 'status', 'kind'];
        if (! in_array($orderBy, $allowedOrderBy, true)) {
            $orderBy = 'publish_at';
        }

        $orderDir = strtolower($orderDir) === 'asc' ? 'asc' : 'desc';
        $q->orderBy($orderBy, $orderDir);

        return $q->paginate($perPage)->appends($filters);
    }

    public function all(array $filters = [], string $orderBy = 'created_at', string $orderDir = 'desc'): Collection
    {
        $q = Campaign::query()->with(['covers', 'documents']);
        $q = CampaignQuery::apply($q, $filters);
        $q->orderBy($orderBy, strtolower($orderDir) === 'asc' ? 'asc' : 'desc');

        return $q->get();
    }

    public function create(CreateCampaignData $data): Campaign
    {
        return DB::transaction(function () use ($data) {
            $payload = $data->toArray();

            $mediaPayload = Arr::only($payload, ['cover_id', 'documents']);
            $campaignPayload = Arr::except($payload, ['cover_id', 'documents']);

            if (empty($campaignPayload['owner_id']) && auth()->check()) {
                $campaignPayload['owner_id'] = auth()->id();
            }

            // meta
            $campaignPayload['meta'] = $this->prepareMeta($payload);

            $campaignPayload = $this->ensureSlug($campaignPayload, null);
            $campaignPayload = $this->normalizeDatetimes($campaignPayload);

            $m = new Campaign;
            $m->fill($campaignPayload)->save();

            $this->syncCover($m, $mediaPayload['cover_id'] ?? null);
            $this->syncDocuments($m, $mediaPayload['documents'] ?? null);

            return $m->fresh(['covers', 'documents']);
        });
    }

    public function update(Campaign $campaign, UpdateCampaignData $data): Campaign
    {
        return DB::transaction(function () use ($campaign, $data) {
            $payload = $data->toArray();

            $mediaPayload = Arr::only($payload, ['cover_id', 'documents']);
            $campaignPayload = Arr::except($payload, ['cover_id', 'documents']);

            if (array_key_exists('owner_id', $campaignPayload) && empty($campaignPayload['owner_id'])) {
                unset($campaignPayload['owner_id']);
            }

            // meta
            if (array_key_exists('meta', $payload) || $this->hasFlatMeta($payload)) {
                $campaignPayload['meta'] = $this->prepareMeta($payload, $campaign->kind);
            }

            $campaignPayload = $this->ensureSlug($campaignPayload, $campaign->id);
            $campaignPayload = $this->normalizeDatetimes($campaignPayload);

            $campaign->fill($campaignPayload)->save();

            if (array_key_exists('documents', $mediaPayload)) {
                $this->syncDocuments($campaign, $mediaPayload['documents']);
            }
            if (array_key_exists('cover_id', $mediaPayload)) {
                $this->syncCover($campaign, $mediaPayload['cover_id']);
            }

            return $campaign->fresh(['covers', 'documents']);
        });
    }

    /** @return array<string,mixed> */
    protected function prepareMeta(array $payload, ?string $fallbackKind = null): array
    {
        if (! empty($payload['meta']) && is_array($payload['meta'])) {
            return $payload['meta'];
        }

        $kind = $payload['kind'] ?? $fallbackKind ?? '';

        $map = [
            'fundraising' => ['goal_amount', 'goal_currency', 'raised_amount'],
            'petition' => ['signature_goal', 'signatures_count'],
            'volunteer' => ['needed_slots', 'filled_slots'],
            'awareness' => ['target_reach', 'current_reach'],
        ];

        $meta = [];
        foreach ($map[$kind] ?? [] as $k) {
            if (array_key_exists($k, $payload)) {
                $meta[$k] = $payload[$k];
            }
        }

        return $meta;
    }

    protected function hasFlatMeta(array $payload): bool
    {
        $keys = [
            'goal_amount', 'goal_currency', 'raised_amount',
            'signature_goal', 'signatures_count',
            'needed_slots', 'filled_slots',
            'target_reach', 'current_reach',
        ];
        foreach ($keys as $k) {
            if (array_key_exists($k, $payload)) {
                return true;
            }
        }

        return false;
    }

    public function delete(Campaign $campaign): void
    {
        $detachedIds = [];

        DB::transaction(function () use ($campaign, &$detachedIds) {
            $ids = $campaign->media()->pluck('media.id')->all();
            $detachedIds = array_map('intval', $ids);

            if ($detachedIds) {
                $campaign->media()->detach($detachedIds);
            }

            $campaign->delete();
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

    public function publish(Campaign $campaign, ?string $at = null): Campaign
    {
        return DB::transaction(function () use ($campaign, $at) {
            $campaign->status = 'published';
            if ($at) {
                $campaign->setAttribute('publish_at', $at);
            }
            $campaign->save();

            return $campaign->fresh();
        });
    }

    public function bySlug(string $slug): ?Campaign
    {
        return Campaign::query()->where('slug', $slug)->first();
    }

    /* ================= helpers ================= */

    protected function normalizeDatetimes(array $data): array
    {
        $tz = config('app.timezone', 'Europe/Amsterdam');

        foreach (['starts_at', 'ends_at', 'publish_at'] as $k) {
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
            $data['slug'] = Campaign::uniqueSlug($data['slug'], $ignoreId);

            return $data;
        }

        if (! empty($data['title'])) {
            $data['slug'] = Campaign::uniqueSlug($data['title'], $ignoreId);
        }

        return $data;
    }

    /* ================= media sync (cover + documents) ================= */

    protected function syncCover(Campaign $campaign, $coverId): void
    {
        if ($coverId === null || $coverId === '') {
            $detached = $campaign->media()
                ->wherePivot('collection', self::COVER_COLLECTION)
                ->pluck('media.id')->all();

            $campaign->media()->wherePivot('collection', self::COVER_COLLECTION)->detach();

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
            $this->syncCover($campaign, null);

            return;
        }

        $this->mediaService->replaceSingle($campaign, $media, self::COVER_COLLECTION, 0);
    }

    protected function syncDocuments(Campaign $campaign, ?array $items): void
    {
        if ($items === null) {
            return;
        }

        if ($items === []) {
            $detachedIds = $campaign->media()
                ->wherePivot('collection', self::DOCS_COLLECTION)
                ->pluck('media.id')->all();

            $campaign->media()->wherePivot('collection', self::DOCS_COLLECTION)->detach();

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
            $this->syncDocuments($campaign, []);

            return;
        }

        $existingIds = Media::withTrashed()
            ->whereIn('id', array_keys($prepared))
            ->pluck('id')->map(fn ($i) => (int) $i)->all();

        if ($existingIds === []) {
            $this->syncDocuments($campaign, []);

            return;
        }

        $currentIds = $campaign->media()
            ->wherePivot('collection', self::DOCS_COLLECTION)
            ->pluck('media.id')->map(fn ($i) => (int) $i)->all();

        $toDetach = array_diff($currentIds, $existingIds);
        if ($toDetach) {
            $campaign->media()->wherePivot('collection', self::DOCS_COLLECTION)->detach($toDetach);

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

        $campaign->media()->syncWithoutDetaching($syncPayload);

        foreach ($syncPayload as $id => $p) {
            DB::table('mediables')
                ->where('mediable_type', Campaign::class)
                ->where('mediable_id', $campaign->id)
                ->where('media_id', $id)
                ->where('collection', self::DOCS_COLLECTION)
                ->update(['order_column' => $p['order_column'], 'updated_at' => now()]);
        }
    }
}
