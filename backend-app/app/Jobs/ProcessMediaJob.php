<?php

namespace App\Jobs;

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Events\MediaProcessingFailed;
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

        // idempotency: اگر قبلاً به نتیجه رسیدیم، دوباره پردازش نکن
        if (in_array($media->status?->value, [
            MediaStatus::READY->value,
            MediaStatus::REJECTED->value,
            MediaStatus::FAILED->value,
        ], true)) {
            Log::info('Process skipped: terminal status', ['id' => $media->id, 'status' => $media->status->value ?? null]);

            return;
        }

        $disk = Storage::disk($media->disk ?: 's3');
        if (! $disk->exists($media->key)) {
            Log::warning('Process skipped: file missing', ['id' => $media->id, 'key' => $media->key]);

            return;
        }

        // وضعیت → PROCESSING
        $media->status = MediaStatus::PROCESSING;
        $media->save();

        // تعیین نوع مدیا با fallback (enum → mime → ext)
        $kind = $this->detectKind($media);
        Log::info('Process kind', [
            'id' => $media->id,
            'kind' => $kind,
            'mime' => $media->mime,
            'ext' => $media->ext,
        ]);

        // دانلود به tmp با پسوند واقعی (بعضی ابزارها به پسوند حساس‌اند)
        $tmpDir = rtrim(sys_get_temp_dir(), '/').'/media_'.$media->id.'_'.bin2hex(random_bytes(4));
        @mkdir($tmpDir, 0777, true);
        $ext = $this->guessExt($media); // jpg|png|mp4|pdf|wav|...
        $srcTmp = "{$tmpDir}/source.{$ext}";
        $this->downloadTo($disk, $media->key, $srcTmp);

        $processed = $media->processed ?? [];
        // نکته: می‌تونی اینو به 'processed/'.$media->id تغییر بدی؛ الان هم‌خوان با بقیه‌ی کدت نگه داشتم:
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
                    // فعلاً PDF؛ بقیه (docx, pptx, …) نیاز به LibreOffice دارند (فاز بعد)
                    if ($ext === 'pdf') {
                        $pdf = new PdfProcessor($media->disk ?: 's3');
                        $res = $pdf->process($srcTmp, $s3BaseDir, $baseNameNoExt);
                        $processed['document'] = $res['variants'];
                        $meta = $media->meta ?? [];
                        $meta['pdf'] = ['pages' => $res['meta']['pages'] ?? null];
                        $media->meta = $meta;
                    } else {
                        Log::info('Document type not implemented (only pdf now)', ['id' => $media->id, 'ext' => $ext]);
                    }
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

            // پاکسازی tmp
            @unlink($srcTmp);
            @rmdir($tmpDir);

            // اگر نوع شناخته‌شده بود ولی خروجی نداشتیم → FAILED تا مشخص باشد
            $nothingProduced = empty($processed['image']) && empty($processed['video']) && empty($processed['document']) && empty($processed['audio']);
            if (in_array($kind, ['image', 'video', 'document', 'audio'], true) && $nothingProduced) {
                $media->status = MediaStatus::FAILED;
                $media->save();
                Log::error('Processing produced no outputs', ['id' => $media->id, 'kind' => $kind]);
                event(new MediaProcessingFailed($media->id, $media->key, 'no-output-produced'));

                return;
            }

            // موفق
            $media->processed = $processed;
            $media->status = MediaStatus::READY;
            $media->save();

        } catch (\Throwable $e) {
            Log::error('Process error', ['id' => $media->id, 'key' => $media->key, 'err' => $e->getMessage()]);
            @unlink($srcTmp);
            @rmdir($tmpDir);

            $media->status = MediaStatus::FAILED;
            $media->save();

            // تا در Horizon → Failed Jobs هم دیده شود
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

        // 2) بر اساس MIME
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
        if (in_array($mime, ['application/pdf', 'application/x-pdf'])) {
            return 'document';
        }

        // 3) بر اساس پسوند
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
            default => 'bin',
        };
    }

    private function imageExts(): array
    {
        // HEIC/AVIF ممکنه در ImageMagick/ffmpeg شما فعال نباشه؛ فعلاً امن‌ها رو می‌ذاریم
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
        // فعلاً فقط pdf؛ اگر خواستی docx/pptx هم add می‌کنیم (با LibreOffice)
        return ['pdf'];
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
