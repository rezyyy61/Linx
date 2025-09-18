<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Share;

use App\Http\Controllers\Controller;
use App\Jobs\LogShareClick;
use App\Models\Share\Share;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ShareResolveController extends Controller
{
    public function __invoke(Request $request, string $code): \Illuminate\Http\Response
    {
        $base = Share::query()->where('short_code', $code);
        $share = (clone $base)->active()->first();

        if (! $share) {
            $exists = $base->first();
            abort($exists ? 410 : 404);
        }

        $share->loadMissing('shareable');

        $model = $share->shareable;
        if (! $model || ! method_exists($model, 'getShareUrl')) {
            abort(404);
        }

        $target = $this->buildTargetUrl($share);

        $ua = (string) ($request->userAgent() ?? '');

        if ($this->isCrawler($ua)) {
            return response()
                ->view('share.preview', [
                    'share' => $share,
                    'target' => $target,
                ])
                ->header('X-Share-Resolve', '1');
        }

        $this->logClick((int) $share->getKey(), $share->short_code, $request);

        return response()->view('share.landing', [
            'share' => $share,
            'target' => $target,
        ]);
    }

    protected function isCrawler(string $ua): bool
    {
        $ua = strtolower($ua);

        return (bool) preg_match('/telegrambot|facebookexternalhit|linkedinbot|twitterbot|slackbot/i', $ua);
    }

    protected function buildTargetUrl(Share $share): string
    {
        /** @var \Illuminate\Database\Eloquent\Model&\App\Models\Share\Contracts\Shareable $shareable */
        $shareable = $share->shareable;

        $url = $shareable->getShareUrl();

        $query = array_filter([
            'utm_source' => $share->utm_source,
            'utm_medium' => $share->utm_medium,
            'utm_campaign' => $share->utm_campaign,
        ]);

        if ($query === []) {
            return $url;
        }

        $delimiter = parse_url($url, PHP_URL_QUERY) ? '&' : '?';

        return $url.$delimiter.http_build_query($query);
    }

    protected function logClick(int $shareId, string $code, Request $request): void
    {
        $ua = (string) ($request->userAgent() ?? '');
        if ($this->isBot($ua)) {
            return;
        }

        $ip = $request->ip() ?? '';
        $salt = (string) config('share.ip_salt', '');
        $ipHash = $ip === '' ? null : hash_hmac('sha256', $ip, $salt);

        dispatch(new LogShareClick(
            shareId: $shareId,
            shortCode: $code,
            ipHash: $ipHash,
            referer: Str::limit((string) $request->headers->get('referer', ''), 65535, ''),
            userAgent: Str::limit($ua, 65535, ''),
            occurredAt: now()->toDateTimeString()
        ));
    }

    protected function isBot(string $ua): bool
    {
        return preg_match('/bot|crawler|spider|slurp|fetch|facebookexternalhit|whatsapp/i', $ua) === 1;
    }
}
