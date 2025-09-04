<?php

namespace App\Jobs;

use App\Enums\MediaStatus;
use App\Events\media\MediaRejected;
use App\Events\media\MediaUpdated;
use App\Models\Media;
use App\Services\Security\VirusScanner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ScanFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 8;

    public function backoff(): array
    {
        return [10, 20, 40, 80, 120, 180, 240];
    }

    public function retryUntil(): \DateTimeInterface
    {
        return now()->addMinutes(5);
    }

    public function __construct(public int $mediaId)
    {
        $this->onQueue('scan');
    }

    public function tags(): array
    {
        return ['media:scan', 'media:'.$this->mediaId];
    }

    public function handle(VirusScanner $scanner): void
    {
        /** @var Media|null $media */
        $media = Media::find($this->mediaId);
        if (! $media) {
            return;
        }

        event(new MediaUpdated($media->id, ['status' => 'SCANNING']));

        if (! config('clamav.enabled', true)) {
            $media->status = MediaStatus::SCANNED;
            $media->save();
            event(new MediaUpdated($media->id, ['status' => 'SCANNED']));
            \App\Jobs\ProcessMediaJob::dispatch($media->id)->onQueue('media');

            return;
        }

        $disk = Storage::disk($media->disk ?: 's3');
        if (! $disk->exists($media->key)) {
            Log::warning('Scan skipped: file missing', ['id' => $media->id, 'key' => $media->key]);
            event(new MediaUpdated($media->id, ['status' => 'FAILED']));

            return;
        }

        try {
            $result = $scanner->scanStream(fn () => $disk->readStream($media->key));
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'Connection refused') || str_contains($e->getMessage(), 'timed out')) {
                $this->release(10);

                return;
            }
            Log::error('Scan error', ['id' => $media->id, 'key' => $media->key, 'err' => $e->getMessage()]);
            $media->status = MediaStatus::FAILED;
            $media->save();
            event(new MediaUpdated($media->id, ['status' => 'FAILED']));

            return;
        }

        $malware = (bool) ($result['malware'] ?? false);
        $reason = (string) ($result['reason'] ?? '');

        $meta = $media->meta ?? [];
        $meta['scan'] = [
            'malware' => $malware,
            'reason' => $reason,
            'time' => now()->toISOString(),
        ];

        if ($malware) {
            try {
                $disk->delete($media->key);
            } catch (\Throwable $e) {
            }
            $media->status = MediaStatus::REJECTED;
            $media->meta = $meta;
            $media->save();
            Log::warning('Infected file removed', ['id' => $media->id, 'key' => $media->key, 'reason' => $reason]);
            event(new MediaRejected($media->id, $media->key, $reason));
            event(new MediaUpdated($media->id, ['status' => 'REJECTED', 'reason' => $reason]));

            return;
        }

        $media->status = MediaStatus::SCANNED;
        $media->meta = $meta;
        $media->save();

        event(new MediaUpdated($media->id, ['status' => 'SCANNED']));
        \App\Jobs\ProcessMediaJob::dispatch($media->id)->onQueue('media');
    }
}
