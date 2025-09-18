<?php

declare(strict_types=1);

namespace App\Services\Share;

use App\Enums\Share\ShareChannel;
use App\Models\Post\Post;
use App\Models\Share\Share;
use App\Services\Share\Contracts\LinkShortener;
use App\Services\Share\Contracts\ShareService as ShareServiceContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ShareService implements ShareServiceContract
{
    public function __construct(
        protected LinkShortener $shortener,
        protected ShareCodeGenerator $codeGenerator
    ) {}

    public function create(
        Model $shareable,
        ShareChannel $channel,
        ?int $creatorId = null,
        array $utm = [],
        ?\DateTimeInterface $expiresAt = null,
        bool $isActive = true
    ): Share {
        if ($this->isRepost($shareable) && $channel === ShareChannel::INTERNAL) {
            $channel = ShareChannel::REPOST;
        }

        $utm = [
            'utm_source' => Arr::get($utm, 'utm_source'),
            'utm_medium' => Arr::get($utm, 'utm_medium', config('share.utm.medium')),
            'utm_campaign' => Arr::get($utm, 'utm_campaign'),
        ];

        return DB::transaction(function () use ($shareable, $channel, $creatorId, $utm, $expiresAt, $isActive) {
            $base = [
                'shareable_type' => $shareable::class,
                'shareable_id' => $shareable->getKey(),
                'creator_id' => $creatorId,
            ];

            $existingRepost = Share::query()
                ->where($base + ['channel' => ShareChannel::REPOST->value])
                ->lockForUpdate()
                ->first();

            if ($existingRepost) {
                $existingRepost->fill([
                    'utm_source' => $utm['utm_source'],
                    'utm_medium' => $utm['utm_medium'],
                    'utm_campaign' => $utm['utm_campaign'],
                    'expires_at' => $expiresAt,
                    'is_active' => $isActive,
                ]);
                $existingRepost->save();

                return $existingRepost;
            }

            $existingInternal = Share::query()
                ->where($base + ['channel' => ShareChannel::INTERNAL->value])
                ->lockForUpdate()
                ->first();

            if ($existingInternal && $channel === ShareChannel::REPOST) {
                $existingInternal->channel = ShareChannel::REPOST->value;
                $existingInternal->fill([
                    'utm_source' => $utm['utm_source'],
                    'utm_medium' => $utm['utm_medium'],
                    'utm_campaign' => $utm['utm_campaign'],
                    'expires_at' => $expiresAt,
                    'is_active' => $isActive,
                ]);
                $existingInternal->save();

                return $existingInternal;
            }

            $identity = $base + ['channel' => $channel->value];

            $existing = Share::query()
                ->where($identity)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $existing->fill([
                    'utm_source' => $utm['utm_source'],
                    'utm_medium' => $utm['utm_medium'],
                    'utm_campaign' => $utm['utm_campaign'],
                    'expires_at' => $expiresAt,
                    'is_active' => $isActive,
                ]);
                $existing->save();

                return $existing;
            }

            $code = $this->uniqueCode();

            return Share::query()->create($identity + [
                'short_code' => $code,
                'utm_source' => $utm['utm_source'],
                'utm_medium' => $utm['utm_medium'],
                'utm_campaign' => $utm['utm_campaign'],
                'expires_at' => $expiresAt,
                'is_active' => $isActive,
            ]);
        });
    }

    private function isRepost(Model $shareable): bool
    {
        return $shareable instanceof Post && (bool) $shareable->getAttribute('repost_of_id');
    }

    public function getShortUrl(string $code): string
    {
        $base = rtrim(config('share.base_url'), '/');
        $prefix = trim(config('share.route_prefix'), '/');

        return $base.'/'.$prefix.'/'.ltrim($code, '/');
    }

    protected function uniqueCode(int $length = 10): string
    {
        do {
            $code = $this->codeGenerator->generate($length);
        } while (Share::query()->where('short_code', $code)->exists());

        return $code;
    }
}
