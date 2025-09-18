<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Share\Share;
use App\Models\Share\ShareClick;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class LogShareClick implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $shareId,
        public string $shortCode,
        public ?string $ipHash,
        public ?string $referer,
        public ?string $userAgent,
        public string $occurredAt
    ) {}

    public function handle(): void
    {
        Log::info('LogShareClick: entered handle', ['share_id' => $this->shareId]);

        $share = Share::query()->find($this->shareId);
        if (! $share || ! $share->is_active) {
            return;
        }

        ShareClick::query()->create([
            'share_id' => $this->shareId,
            'short_code' => $this->shortCode,
            'ip_hash' => $this->ipHash,
            'referer' => $this->referer,
            'user_agent' => $this->userAgent,
            'occurred_at' => $this->occurredAt,
        ]);
    }
}
