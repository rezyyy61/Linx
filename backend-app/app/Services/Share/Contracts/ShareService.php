<?php

declare(strict_types=1);

namespace App\Services\Share\Contracts;

use App\Enums\Share\ShareChannel;
use App\Models\Share\Share;
use Illuminate\Database\Eloquent\Model;

interface ShareService
{
    public function create(Model $shareable, ShareChannel $channel, ?int $creatorId = null, array $utm = [], ?\DateTimeInterface $expiresAt = null, bool $isActive = true): Share;

    public function getShortUrl(string $code): string;
}
