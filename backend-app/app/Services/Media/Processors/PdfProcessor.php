<?php

namespace App\Services\Media\Processors;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class PdfProcessor
{
    public function __construct(private string $disk = 's3') {}

    /**
     * می‌تواند ورودی غیر-PDF بگیرد؛ اگر PDF نبود، به PDF تبدیل می‌کنیم و سپس preview می‌سازیم.
     *
     * @return array{
     *   meta: array{pages: int|null},
     *   variants: array<int, array{key:string, mime:string, role:string}>
     * }
     */
    public function process(string $sourcePath, string $s3BaseDir, string $basenameNoExt): array
    {
        // 0) مطمئن شو باینری‌های لازم وجود دارند
        $this->ensureBin('pdfinfo');
        $this->ensureBin('pdftoppm');

        // 1) اگر ورودی PDF نیست، با LibreOffice به PDF تبدیل کن
        [$pdfPath, $tmpPdfCreated] = $this->ensurePdf($sourcePath);

        // 2) تعداد صفحات
        $pages = $this->pages($pdfPath);

        // 3) preview از صفحه اول (JPEG، scale حداکثر 2048px)
        $outPrefix = sys_get_temp_dir()."/{$basenameNoExt}_preview";
        $this->runOrFail([
            'pdftoppm',
            '-jpeg', '-singlefile',
            '-scale-to', '2048',
            $pdfPath, $outPrefix,
        ]);
        $jpgTmp = "{$outPrefix}.jpg";
        $jpgKey = "{$s3BaseDir}/{$basenameNoExt}_preview.jpg";
        $this->upload($jpgTmp, $jpgKey, 'image/jpeg');
        @unlink($jpgTmp);

        // 4) اگر PDF موقتی ساختیم، پاکش کن
        if ($tmpPdfCreated && is_file($pdfPath)) {
            @unlink($pdfPath);
        }

        return [
            'meta' => ['pages' => $pages],
            'variants' => [
                ['key' => $jpgKey, 'mime' => 'image/jpeg', 'role' => 'preview'],
            ],
        ];
    }

    /**
     * اگر ورودی PDF نیست، با LibreOffice به PDF تبدیل می‌کنیم و مسیر PDF را برمی‌گردانیم.
     *
     * @return array{0:string,1:bool} [pdfPath, tmpCreated]
     */
    private function ensurePdf(string $src): array
    {
        $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION));
        if ($ext === 'pdf') {
            return [$src, false];
        }

        $soffice = $this->resolveBin('soffice');

        $outDir = dirname($src);
        $outPdf = $outDir.'/'.pathinfo($src, PATHINFO_FILENAME).'.pdf';

        // پروفایل موقت و قابل‌نوشتن برای LO
        $profileDir = sys_get_temp_dir().'/lo_'.bin2hex(random_bytes(5));
        @mkdir($profileDir, 0777, true);
        $profileUri = 'file://'.$profileDir; // میشه شبیه file:///tmp/lo_xxx

        $cmd = [
            $soffice,
            '--headless', '--nologo', '--nofirststartwizard', '--nolockcheck',
            '-env:UserInstallation='.$profileUri,
            '--convert-to', 'pdf',
            '--outdir', $outDir,
            $src,
        ];

        // ENV های امن برای اجرا
        $env = [
            'HOME' => sys_get_temp_dir(),
            'XDG_CONFIG_HOME' => sys_get_temp_dir(),
            'LANG' => 'en_US.UTF-8',
        ];

        $this->runOrFail($cmd, $env);

        if (! is_file($outPdf)) {
            throw new \RuntimeException('LibreOffice: converted PDF not found for '.$src);
        }

        // پاکسازی پروفایل موقتی
        try {
            @unlink($profileDir.'/registrymodifications.xcu');
            @rmdir($profileDir);
        } catch (\Throwable) {
        }

        return [$outPdf, true];
    }

    private function resolveBin(string $bin): string
    {
        $p = new \Symfony\Component\Process\Process(['sh', '-lc', 'command -v '.escapeshellarg($bin)]);
        $p->setTimeout(5);
        $p->run();
        $out = trim($p->getOutput());
        if ($p->isSuccessful() && $out !== '') {
            return $out;
        }

        foreach (['/usr/bin/'.$bin, '/usr/local/bin/'.$bin, '/usr/lib/libreoffice/program/'.$bin] as $path) {
            if (is_file($path) || is_link($path)) {
                return $path;
            }
        }
        throw new \RuntimeException("Required binary not available: {$bin}");
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
        if ($stream === false) {
            throw new \RuntimeException('Could not open local file: '.$localPath);
        }
        Storage::disk($this->disk)->put($key, $stream, [
            'visibility' => 'public',
            'ContentType' => $mime,
        ]);
        fclose($stream);
    }

    private function runOrFail(array $cmd, array $env = []): void
    {
        // env پیش‌فرض + env‌های پاس‌داده‌شده
        $defaultEnv = [
            'HOME' => getenv('HOME') ?: '/tmp',
            'XDG_CONFIG_HOME' => getenv('XDG_CONFIG_HOME') ?: '/tmp',
        ];
        $p = new Process($cmd, null, $env + $defaultEnv);
        $p->setTimeout(180);
        $p->run();
        if (! $p->isSuccessful()) {
            throw new \RuntimeException('Process failed: '.implode(' ', array_map('escapeshellarg', $cmd)).' :: '.$p->getErrorOutput());
        }
    }

    private function ensureBin(string $bin): void
    {
        // 1) ساده‌ترین و مطمئن‌ترین چک: آیا در PATH هست؟
        $p = new \Symfony\Component\Process\Process(['sh', '-lc', 'command -v '.escapeshellarg($bin)]);
        $p->setTimeout(5);
        $p->run();
        if ($p->isSuccessful() && trim($p->getOutput()) !== '') {
            return;
        }

        // 2) مسیرهای رایج fallback (Alpine/Debian)
        foreach (['/usr/bin/'.$bin, '/usr/local/bin/'.$bin, '/usr/lib/libreoffice/program/'.$bin] as $path) {
            if (is_file($path) || is_link($path)) {
                return;
            }
        }

        throw new \RuntimeException("Required binary not available: {$bin}");
    }
}
