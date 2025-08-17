<?php

namespace App\Services\Media\Processors;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class VideoProcessor
{
    public function __construct(private string $disk = 's3') {}

    public function process(string $sourcePath, string $s3BaseDir, string $basenameNoExt): array
    {
        // 1) probe
        $probe = $this->ffprobe($sourcePath);

        // 2) poster (JPG از ثانیه‌ی 1، عرض حداکثر 1280)
        $posterTmp = sys_get_temp_dir()."/{$basenameNoExt}_poster.jpg";
        $this->runOrFail([
            'ffmpeg', '-ss', '1', '-i', $sourcePath,
            '-vframes', '1',
            '-vf', 'scale=\'min(1280,iw)\':-2',
            '-q:v', '2',
            $posterTmp,
        ]);
        $posterKey = "{$s3BaseDir}/{$basenameNoExt}_poster.jpg";
        $this->upload($posterTmp, $posterKey, 'image/jpeg');

        // 3) mp4 (H.264 + AAC، faststart برای استریم)
        $mp4Tmp = sys_get_temp_dir()."/{$basenameNoExt}.mp4";
        $this->runOrFail([
            'ffmpeg', '-y', '-i', $sourcePath,
            '-vf', 'scale=\'min(1280,iw)\':-2',
            '-c:v', 'libx264', '-preset', 'veryfast', '-crf', '23',
            '-profile:v', 'main', '-pix_fmt', 'yuv420p',
            '-movflags', '+faststart',
            '-c:a', 'aac', '-b:a', '128k',
            $mp4Tmp,
        ]);
        $mp4Key = "{$s3BaseDir}/{$basenameNoExt}.mp4";
        $this->upload($mp4Tmp, $mp4Key, 'video/mp4');

        // پاک‌سازی tmp
        @unlink($posterTmp);
        @unlink($mp4Tmp);

        return [
            'meta' => $probe, // width/height/duration...
            'variants' => [
                ['key' => $posterKey, 'mime' => 'image/jpeg', 'role' => 'poster'],
                ['key' => $mp4Key,    'mime' => 'video/mp4',  'role' => 'mp4_1280'],
            ],
        ];
    }

    private function upload(string $localPath, string $key, string $mime): void
    {
        $stream = fopen($localPath, 'rb');
        Storage::disk($this->disk)->put($key, $stream, [
            'visibility' => 'public',
            'ContentType' => $mime,
        ]);
        if (is_resource($stream)) {
            fclose($stream);
        }
    }

    private function ffprobe(string $path): array
    {
        $p = new Process(['ffprobe', '-v', 'error', '-show_format', '-show_streams', '-print_format', 'json', $path]);
        $p->setTimeout(30);
        $p->run();
        if (! $p->isSuccessful()) {
            return ['ok' => false];
        }

        $json = json_decode($p->getOutput(), true);
        $v = collect($json['streams'] ?? [])->firstWhere('codec_type', 'video') ?? [];

        return [
            'ok' => true,
            'width' => $v['width'] ?? null,
            'height' => $v['height'] ?? null,
            'duration' => isset($json['format']['duration']) ? (float) $json['format']['duration'] : null,
            'format' => $json['format']['format_name'] ?? null,
        ];
    }

    private function runOrFail(array $cmd): void
    {
        $p = new Process($cmd);
        $p->setTimeout(600); // تا 10 دقیقه برای ویدیو
        $p->run();
        if (! $p->isSuccessful()) {
            throw new \RuntimeException('Process failed: '.implode(' ', $cmd).' :: '.$p->getErrorOutput());
        }
    }
}
