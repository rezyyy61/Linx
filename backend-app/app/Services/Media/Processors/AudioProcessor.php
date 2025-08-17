<?php

namespace App\Services\Media\Processors;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class AudioProcessor
{
    public function __construct(private string $disk = 's3') {}

    public function process(string $sourcePath, string $s3BaseDir, string $basenameNoExt): array
    {
        // 1) ffprobe برای متادیتا
        $probe = $this->ffprobe($sourcePath);

        // 2) نرمال‌سازی + AAC (m4a)
        // اگر aac در ffmpeg شما نیست، موقتاً: -c:a libmp3lame و mime = audio/mpeg و پسوند .mp3
        $m4aTmp = sys_get_temp_dir()."/{$basenameNoExt}.m4a";
        $cmd = [
            'ffmpeg', '-y', '-i', $sourcePath,
            '-vn',
            '-filter:a', 'loudnorm=I=-16:TP=-1.5:LRA=11',
            '-c:a', 'aac', '-b:a', '192k',
            $m4aTmp,
        ];
        $this->runOrFail($cmd);

        $m4aKey = "{$s3BaseDir}/{$basenameNoExt}.m4a";
        $this->upload($m4aTmp, $m4aKey, 'audio/mp4');

        @unlink($m4aTmp);

        return [
            'meta' => $probe,
            'variants' => [
                ['key' => $m4aKey, 'mime' => 'audio/mp4', 'role' => 'aac_m4a_192'],
            ],
        ];
    }

    private function upload(string $localPath, string $key, string $mime): void
    {
        $stream = fopen($localPath, 'rb');
        Storage::disk($this->disk)->put($key, $stream, [
            'visibility' => 'public',
            'mimetype' => $mime,  // مهم: mimetype (نه ContentType)
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
        $a = collect($json['streams'] ?? [])->firstWhere('codec_type', 'audio') ?? [];

        return [
            'ok' => true,
            'duration' => isset($json['format']['duration']) ? (float) $json['format']['duration'] : null,
            'bit_rate' => isset($json['format']['bit_rate']) ? (int) $json['format']['bit_rate'] : null,
            'channels' => $a['channels'] ?? null,
            'sample_rate' => isset($a['sample_rate']) ? (int) $a['sample_rate'] : null,
            'codec' => $a['codec_name'] ?? null,
        ];
    }

    private function runOrFail(array $cmd): void
    {
        $p = new Process($cmd);
        $p->setTimeout(600);
        $p->run();
        if (! $p->isSuccessful()) {
            throw new \RuntimeException('Process failed: '.implode(' ', $cmd).' :: '.$p->getErrorOutput());
        }
    }
}
