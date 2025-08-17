<?php

namespace App\Services\Security;

use Socket\Raw\Factory;
use Xenolope\Quahog\Client;

class VirusScanner
{
    /**
     * @param \Closure():resource $streamOpener  باید یک stream خواندنی بده (مثلاً Storage::disk(...)->readStream($key))
     * @return array{filename:string, malware:bool, reason:string}
     */
    public function scanStream(\Closure $streamOpener): array
    {
        $host    = (string) config('clamav.host', 'clamav');
        $port    = (int)    config('clamav.port', 3310);
        $timeout = (int)    config('clamav.timeout', 30);
        $max     = (int)    config('clamav.max_bytes', 52_428_800); // ~50MB
        $chunk   = 262_144; // 256KB

        $socket  = (new Factory())->createClient("tcp://{$host}:{$port}", $timeout);
        $client  = new Client($socket, $timeout);

        $handle = $streamOpener();
        if (!is_resource($handle)) {
            throw new \RuntimeException('Scan stream could not be opened');
        }

        try {
            // ترجیح: API جدید که مستقیم resource می‌گیرد
            /** @phpstan-ignore-next-line  supporting multiple Quahog versions */
            if (is_callable([$client, 'scanResourceStream'])) {
                /** @var mixed $raw */
                $raw = $client->scanResourceStream($handle, $chunk);
                return $this->normalizeResult($raw);
            }

            // fallback: تا سقف $max بخون و به scanStream(string) بده
            $buf  = '';
            $read = 0;
            while (!feof($handle) && $read < $max) {
                $piece = fread($handle, $chunk);
                if ($piece === false) break;
                $buf  .= $piece;
                $read += strlen($piece);
            }

            /** @var mixed $raw */
            $raw = $client->scanStream($buf); // امضای سازگارتر بین نسخه‌ها
            return $this->normalizeResult($raw);

        } finally {
            fclose($handle);
        }
    }

    /**
     * @param mixed $raw
     * @return array{filename:string, malware:bool, reason:string}
     */
    private function normalizeResult($raw): array
    {
        $filename = 'stream';
        $malware  = false;
        $reason   = 'OK';

        if (is_array($raw)) {
            // نمونه‌های رایج آرایه‌ای
            $filename = (string) ($raw['filename'] ?? 'stream');
            $malware  = (bool)   ($raw['malware']  ?? ($raw['found'] ?? false));
            $reason   = (string) ($raw['reason']   ?? ($raw['clamav'] ?? 'OK'));
        } elseif (is_string($raw)) {
            // بعضی نسخه‌ها فقط string می‌دن: "stream: OK" یا "... FOUND"
            $reason  = $raw;
            $malware = stripos($raw, 'FOUND') !== false;
        } elseif (is_object($raw)) {
            // برخی wrapperها شیء برمی‌گردونن
            if (method_exists($raw, 'isFound')) {
                $malware = (bool) $raw->isFound();
            }
            if (method_exists($raw, 'getReason')) {
                $reason = (string) $raw->getReason();
            }
        }

        return ['filename' => $filename, 'malware' => $malware, 'reason' => $reason];
    }
}
