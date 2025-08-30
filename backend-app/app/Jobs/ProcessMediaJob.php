<?php

namespace App\Jobs;

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Events\media\MediaProcessingFailed;
use App\Events\media\MediaUpdated;
use App\Models\Media;
use App\Services\Media\Processors\AudioProcessor;
use App\Services\Media\Processors\ImageProcessor;
use App\Services\Media\Processors\PdfProcessor;
use App\Services\Media\Processors\VideoProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $mediaId)
    {
        $this->onQueue('media');
    }

    public function tags(): array
    {
        return ['media:process', 'media:'.$this->mediaId];
    }

    public function handle(): void
    {
        /** @var Media|null $media */
        $media = Media::find($this->mediaId);
        if (! $media) {
            return;
        }

        if (in_array($media->status?->value, [
            MediaStatus::READY->value,
            MediaStatus::REJECTED->value,
            MediaStatus::FAILED->value,
        ], true)) {
            Log::info('Process skipped: terminal status', ['id' => $media->id, 'status' => $media->status->value ?? null]);
            event(new MediaUpdated($media->id, ['status' => strtoupper($media->status->value ?? 'READY')]));

            return;
        }

        $disk = Storage::disk($media->disk ?: 's3');
        if (! $disk->exists($media->key)) {
            Log::warning('Process skipped: file missing', ['id' => $media->id, 'key' => $media->key]);
            event(new MediaUpdated($media->id, ['status' => 'FAILED']));

            return;
        }

        // → PROCESSING
        $media->status = MediaStatus::PROCESSING;
        $media->save();
        event(new MediaUpdated($media->id, ['status' => 'PROCESSING']));

        $kind = $this->detectKind($media);

        $tmpDir = rtrim(sys_get_temp_dir(), '/').'/media_'.$media->id.'_'.bin2hex(random_bytes(4));
        @mkdir($tmpDir, 0777, true);
        $ext = $this->guessExt($media); // jpg|png|mp4|pdf|wav|...
        $srcTmp = "{$tmpDir}/source.{$ext}";
        $this->downloadTo($disk, $media->key, $srcTmp);

        $processed = $media->processed ?? [];
        $baseNameNoExt = pathinfo($media->key, PATHINFO_FILENAME);
        $s3BaseDir = "processed/{$baseNameNoExt}";

        try {
            switch ($kind) {
                case 'image':
                    $image = new ImageProcessor($media->disk ?: 's3');
                    $variants = $image->process($srcTmp, $s3BaseDir, $baseNameNoExt);
                    $processed['image'] = $variants;
                    break;

                case 'video':
                    $video = new VideoProcessor($media->disk ?: 's3');
                    $res = $video->process($srcTmp, $s3BaseDir, $baseNameNoExt);
                    $processed['video'] = $res['variants'];
                    // meta عمومی ویدیو
                    $media->duration = $res['meta']['duration'] ?? $media->duration;
                    $media->width = $res['meta']['width'] ?? $media->width;
                    $media->height = $res['meta']['height'] ?? $media->height;
                    break;

                case 'document':
                    $doc = new PdfProcessor($media->disk ?: 's3');
                    $res = $doc->process($srcTmp, $s3BaseDir, $baseNameNoExt);
                    $processed['document'] = $res['variants'];
                    $meta = $media->meta ?? [];
                    $meta['document'] = [
                        'pages' => $res['meta']['pages'] ?? null,
                        'converted' => true,
                    ];
                    $media->meta = $meta;
                    break;

                case 'audio':
                    $audio = new AudioProcessor($media->disk ?: 's3');
                    $res = $audio->process($srcTmp, $s3BaseDir, $baseNameNoExt);
                    $processed['audio'] = $res['variants'];
                    // meta عمومی صوت
                    $media->duration = $res['meta']['duration'] ?? $media->duration;
                    $meta = $media->meta ?? [];
                    $meta['audio'] = [
                        'bit_rate' => $res['meta']['bit_rate'] ?? null,
                        'channels' => $res['meta']['channels'] ?? null,
                        'sample_rate' => $res['meta']['sample_rate'] ?? null,
                        'codec' => $res['meta']['codec'] ?? null,
                    ];
                    $media->meta = $meta;
                    break;

                default:
                    Log::warning('Unknown media kind, skipping processing', ['id' => $media->id, 'kind' => $kind]);
                    break;

            }

            @unlink($srcTmp);
            @rmdir($tmpDir);

            $nothingProduced = empty($processed['image']) && empty($processed['video']) && empty($processed['document']) && empty($processed['audio']);
            if (in_array($kind, ['image', 'video', 'document', 'audio'], true) && $nothingProduced) {
                $media->status = MediaStatus::FAILED;
                $media->save();
                Log::error('Processing produced no outputs', ['id' => $media->id, 'kind' => $kind]);
                event(new MediaProcessingFailed($media->id, $media->key, 'no-output-produced'));
                event(new MediaUpdated($media->id, ['status' => 'FAILED']));

                return;
            }

            $media->processed = $processed;
            $media->status = MediaStatus::READY;
            $media->save();
            event(new MediaUpdated($media->id, [
                'status' => 'READY',
                'processed' => $processed,
                'public_url' => $media->public_url ?? null,
                'meta' => $media->meta ?? null,
                'width' => $media->width,
                'height' => $media->height,
                'duration' => $media->duration,
            ]));

        } catch (\Throwable $e) {
            Log::error('Process error', ['id' => $media->id, 'key' => $media->key, 'err' => $e->getMessage()]);
            @unlink($srcTmp);
            @rmdir($tmpDir);

            $media->status = MediaStatus::FAILED;
            $media->save();
            event(new MediaUpdated($media->id, ['status' => 'FAILED']));
            throw $e;
        }
    }

    private function detectKind(Media $media): string
    {
        // 1) Enum
        if ($media->type instanceof MediaType) {
            return match ($media->type) {
                MediaType::IMAGE => 'image',
                MediaType::VIDEO => 'video',
                MediaType::AUDIO => 'audio',
                MediaType::DOCUMENT => 'document',
            };
        }

        $mime = strtolower((string) $media->mime);
        if (str_starts_with($mime, 'image/')) {
            return 'image';
        }
        if (str_starts_with($mime, 'video/')) {
            return 'video';
        }
        if (str_starts_with($mime, 'audio/')) {
            return 'audio';
        }
        $docMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'application/vnd.ms-excel.sheet.macroenabled.12',
            'application/vnd.ms-powerpoint.presentation.macroenabled.12',
        ];
        if (in_array($mime, $docMimes, true)) {
            return 'document';
        }

        $ext = strtolower($media->ext ?? pathinfo($media->key, PATHINFO_EXTENSION));
        if (in_array($ext, $this->imageExts(), true)) {
            return 'image';
        }
        if (in_array($ext, $this->videoExts(), true)) {
            return 'video';
        }
        if (in_array($ext, $this->audioExts(), true)) {
            return 'audio';
        }
        if (in_array($ext, $this->docExts(), true)) {
            return 'document';
        }

        return 'unknown';
    }

    private function guessExt(Media $media): string
    {
        $ext = strtolower($media->ext ?: pathinfo($media->key, PATHINFO_EXTENSION));
        if ($ext) {
            return $ext;
        }

        $mime = strtolower((string) $media->mime);

        return match (true) {
            str_starts_with($mime, 'image/') => 'jpg',
            str_starts_with($mime, 'video/') => 'mp4',
            str_starts_with($mime, 'audio/') => 'm4a',
            // ↓ اگر MIME سند بود ولی پسوند نداریم
            in_array($mime, [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'text/plain',
                'application/vnd.ms-excel.sheet.macroenabled.12',
                'application/vnd.ms-powerpoint.presentation.macroenabled.12',
            ], true) => 'pdf',
            default => 'bin',
        };
    }

    private function imageExts(): array
    {
        return ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'tiff', 'tif'];
    }

    private function videoExts(): array
    {
        return ['mp4', 'mov', 'mkv', 'webm', 'avi'];
    }

    private function audioExts(): array
    {
        return ['mp3', 'aac', 'm4a', 'wav', 'flac', 'ogg', 'oga'];
    }

    private function docExts(): array
    {
        return ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'];
    }

    private function downloadTo($disk, string $key, string $destPath): void
    {
        $read = $disk->readStream($key);
        if (! is_resource($read)) {
            throw new \RuntimeException('Could not open read stream for '.$key);
        }
        $write = fopen($destPath, 'wb');
        stream_copy_to_stream($read, $write);
        fclose($read);
        fclose($write);
    }
}
