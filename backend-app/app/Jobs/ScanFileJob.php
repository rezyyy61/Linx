<?php

namespace App\Jobs;

use App\Enums\MediaStatus;
use App\Events\MediaRejected;
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

    public function __construct(public int $mediaId)
    {
        $this->onQueue('scan');
        // $this->afterCommit(); // اگر finalize داخل تراکنش است
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

        // اگر اسکن غیرفعاله، پایپ‌لاین رو متوقف نکن
        if (! config('clamav.enabled', true)) {
            $media->status = MediaStatus::SCANNED;
            $media->save();
            \App\Jobs\ProcessMediaJob::dispatch($media->id);

            return;
        }

        $disk = Storage::disk($media->disk ?: 's3');
        if (! $disk->exists($media->key)) {
            Log::warning('Scan skipped: file missing', ['id' => $media->id, 'key' => $media->key]);

            return;
        }

        try {
            $result = $scanner->scanStream(fn () => $disk->readStream($media->key));
        } catch (\Throwable $e) {
            // انتخاب با خودته: یا fail کنی یا با احتیاط جلو بری
            Log::error('Scan error', ['id' => $media->id, 'key' => $media->key, 'err' => $e->getMessage()]);
            $media->status = MediaStatus::FAILED;
            $media->save();

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
            return;
        }

        // سالم: یک‌بار و فقط همین‌جا به SCANNED ببر و جاب بعدی را صف کن
        $media->status = MediaStatus::SCANNED;
        $media->meta = $meta;
        $media->save();

        \App\Jobs\ProcessMediaJob::dispatch($media->id);
    }
}
