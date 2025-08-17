<?php

namespace App\Services\Media\Processors;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class PdfProcessor
{
    public function __construct(private string $disk = 's3') {}

    public function process(string $sourcePath, string $s3BaseDir, string $basenameNoExt): array
    {
        // 1) تعداد صفحات (pdfinfo)
        $pages = $this->pages($sourcePath);

        // 2) preview صفحه اول (JPEG، طول/عرض max=2048)
        $outPrefix = sys_get_temp_dir()."/{$basenameNoExt}_preview";
        $this->runOrFail([
            'pdftoppm',
            '-jpeg', '-singlefile',
            '-scale-to', '2048',
            $sourcePath, $outPrefix,
        ]);
        $jpgTmp = "{$outPrefix}.jpg";
        $jpgKey = "{$s3BaseDir}/{$basenameNoExt}_preview.jpg";
        $this->upload($jpgTmp, $jpgKey, 'image/jpeg');

        @unlink($jpgTmp);

        return [
            'meta' => ['pages' => $pages],
            'variants' => [
                ['key' => $jpgKey, 'mime' => 'image/jpeg', 'role' => 'preview'],
            ],
        ];
    }

    private function pages(string $path): ?int
    {
        $p = new Process(['pdfinfo', $path]);
        $p->setTimeout(20);
        $p->run();
        if (! $p->isSuccessful()) {
            return null;
        }

        foreach (explode("\n", $p->getOutput()) as $line) {
            if (stripos($line, 'Pages:') === 0) {
                return (int) trim(substr($line, strlen('Pages:')));
            }
        }

        return null;
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
