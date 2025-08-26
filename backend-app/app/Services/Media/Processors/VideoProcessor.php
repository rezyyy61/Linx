<?php

namespace App\Services\Media\Processors;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class VideoProcessor
{
    public function __construct(private string $disk = 's3') {}

    /**
     * Fast path: try remux-only; otherwise very-fast CPU transcode.
     * Output: MP4 + poster + slim metadata (method signature unchanged).
     */
    public function process(string $sourcePath, string $s3BaseDir, string $basenameNoExt): array
    {
        // Step 1) Probe input to decide the path
        $probe = $this->ffprobe($sourcePath);

        // Step 2) Prepare unique temp files
        $uid = uniqid($basenameNoExt.'_', true);
        $mp4Tmp = sys_get_temp_dir()."/{$uid}.mp4";
        $posterTmp = sys_get_temp_dir()."/{$uid}_poster.jpg";

        try {
            // Step 3) Path A: remux-only (no re-encode, fastest)
            if ($this->canRemuxMp4($probe)) {
                $this->runOrFail([
                    'ffmpeg', '-y', '-hide_banner', '-loglevel', 'error',
                    '-fflags', '+genpts',
                    '-i', $sourcePath,
                    // keep all streams, drop attached cover image, drop subs/data
                    '-map', '0', '-map', '-0:v:m:attached_pic', '-sn', '-dn',
                    '-c:v', 'copy', '-c:a', 'copy',
                    '-movflags', '+faststart',
                    $mp4Tmp,
                ]);

                // Step 4) Poster: accurate seek after -i, 1280x720 pad, square pixels
                $this->makePoster($mp4Tmp, $this->thumbTime($probe), $posterTmp);
            }
            // Step 3b) Path B: very-fast CPU transcode to a web-friendly MP4
            else {
                $vf = 'scale=w=720:h=-2:flags=fast_bilinear:force_original_aspect_ratio=decrease,setsar=1';

                $cmd = [
                    'ffmpeg', '-y', '-hide_banner', '-loglevel', 'error',
                    '-i', $sourcePath,
                    '-map', '0:v:0', '-map', '0:a?', '-sn', '-dn',
                    '-vf', $vf,
                    '-c:v', 'libx264', '-preset', 'ultrafast', '-crf', '28',
                    '-pix_fmt', 'yuv420p',
                    '-movflags', '+faststart',
                ];

                if (($probe['a_codec'] ?? null) === 'aac') {
                    $cmd = array_merge($cmd, ['-c:a', 'copy']);
                } else {
                    $cmd = array_merge($cmd, ['-c:a', 'aac', '-b:a', '96k']);
                }

                $cmd[] = $mp4Tmp;
                $this->runOrFail($cmd);

                // Step 4) Poster from the freshly created MP4
                $this->makePoster($mp4Tmp, $this->thumbTime($probe), $posterTmp);
            }

            // Step 5) Upload results
            $posterKey = "{$s3BaseDir}/{$basenameNoExt}_poster.jpg";
            $mp4Key = "{$s3BaseDir}/{$basenameNoExt}.mp4";

            $this->upload($posterTmp, $posterKey, 'image/jpeg');
            $this->upload($mp4Tmp, $mp4Key, 'video/mp4');

            // Step 6) Return compact metadata and variants
            return [
                'meta' => $this->slimMeta($probe),
                'variants' => [
                    ['key' => $posterKey, 'mime' => 'image/jpeg', 'role' => 'poster'],
                    ['key' => $mp4Key,    'mime' => 'video/mp4',  'role' => 'mp4_1280'],
                ],
            ];
        } finally {
            @unlink($posterTmp);
            @unlink($mp4Tmp);
        }
    }

    // --- Helpers ---

    /** Remux-only if: H.264 + yuv420p, width ≤ 1280, audio AAC or none */
    private function canRemuxMp4(array $p): bool
    {
        return ($p['v_codec'] ?? null) === 'h264'
            && ($p['v_pix_fmt'] ?? null) === 'yuv420p'
            && (int) ($p['width'] ?? 0) <= 1280
            && in_array($p['a_codec'] ?? null, [null, 'aac'], true);
    }

    /** Poster time: pick a meaningful frame (10% of duration, clamped) */
    private function thumbTime(array $p): float
    {
        $dur = (float) ($p['duration'] ?? 0.0);

        return $dur > 0 ? min(max(1.0, $dur * 0.10), 15.0) : 1.0;
    }

    /** Build a high-quality poster: accurate seek, 1280x720 pad, square pixels */
    private function makePoster(string $inputPath, float $seconds, string $posterPath): void
    {
        $this->runOrFail([
            'ffmpeg', '-y', '-hide_banner', '-loglevel', 'error',
            '-i', $inputPath,
            '-ss', sprintf('%.3f', $seconds),
            '-map', '0:v:0', '-an', '-sn',
            '-frames:v', '1',
            // fit inside 1280x720 without crop, then pad to exact 1280x720, enforce square pixels
            '-vf', 'scale=iw*min(1280/iw\\,720/ih):ih*min(1280/iw\\,720/ih),pad=1280:720:(1280-iw)/2:(720-ih)/2,setsar=1',
            '-q:v', '2',
            $posterPath,
        ]);
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

    /** Fast ffprobe with essential fields */
    private function ffprobe(string $path): array
    {
        $p = new Process(['ffprobe', '-v', 'error', '-show_format', '-show_streams', '-print_format', 'json', $path]);
        $p->setTimeout(30)->run();
        if (! $p->isSuccessful()) {
            return ['ok' => false];
        }

        $json = json_decode($p->getOutput(), true);
        $streams = $json['streams'] ?? [];
        $v = collect($streams)->firstWhere('codec_type', 'video') ?? [];
        $a = collect($streams)->firstWhere('codec_type', 'audio') ?? [];

        return [
            'ok' => true,
            'width' => $v['width'] ?? null,
            'height' => $v['height'] ?? null,
            'duration' => isset($json['format']['duration']) ? (float) $json['format']['duration'] : null,
            'format' => $json['format']['format_name'] ?? null,
            'v_codec' => $v['codec_name'] ?? null,
            'v_pix_fmt' => $v['pix_fmt'] ?? null,
            'a_codec' => $a['codec_name'] ?? null,
        ];
    }

    private function runOrFail(array $cmd): void
    {
        $p = new Process($cmd);
        $p->setTimeout(600);
        $p->run();
        if (! $p->isSuccessful()) {
            throw new \RuntimeException('Process failed: '.implode(' ', array_map('strval', $cmd)).' :: '.$p->getErrorOutput());
        }
    }

    private function slimMeta(array $p): array
    {
        return [
            'ok' => $p['ok'] ?? false,
            'width' => $p['width'] ?? null,
            'height' => $p['height'] ?? null,
            'duration' => $p['duration'] ?? null,
            'v_codec' => $p['v_codec'] ?? null,
            'a_codec' => $p['a_codec'] ?? null,
        ];
    }
}
