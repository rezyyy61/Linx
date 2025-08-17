<?php

namespace App\Services\Media\Processors;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class ImageProcessor
{
    public function __construct(
        private string $disk = 's3'
    ) {}

    public function process(string $sourcePath, string $s3BaseDir, string $basenameNoExt): array
    {
        $out = [];

        $jpgTmp = sys_get_temp_dir()."/{$basenameNoExt}_large.jpg";
        $this->runOrFail(['magick', $sourcePath, '-auto-orient', '-strip', '-resize', '2048x2048>', '-quality', '82', $jpgTmp]);
        $jpgKey = "{$s3BaseDir}/{$basenameNoExt}_large.jpg";
        $this->upload($jpgTmp, $jpgKey, 'image/jpeg');
        $out[] = ['key' => $jpgKey, 'mime' => 'image/jpeg', 'role' => 'large'];

        $webpTmp = sys_get_temp_dir()."/{$basenameNoExt}.webp";
        $this->runOrFail(['cwebp', '-q', '82', $jpgTmp, '-o', $webpTmp]);
        $webpKey = "{$s3BaseDir}/{$basenameNoExt}.webp";
        $this->upload($webpTmp, $webpKey, 'image/webp');
        $out[] = ['key' => $webpKey, 'mime' => 'image/webp', 'role' => 'webp'];

        @unlink($jpgTmp);
        @unlink($webpTmp);

        return $out;
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

    private function runOrFail(array $cmd): void
    {
        $p = new Process($cmd);
        $p->setTimeout(120);
        $p->run();
        if (! $p->isSuccessful()) {
            throw new \RuntimeException('Process failed: '.implode(' ', $cmd).' :: '.$p->getErrorOutput());
        }
    }
}
