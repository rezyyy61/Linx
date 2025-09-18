<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Share;

use App\Enums\Share\ShareChannel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Share\CreateShareRequest;
use App\Models\Share\Contracts\Shareable;
use App\Services\Share\Contracts\ShareGuard;
use App\Services\Share\Contracts\ShareService;
use Illuminate\Http\JsonResponse;

class ShareController extends Controller
{
    public function __construct(
        protected ShareService $service,
        protected ShareGuard $guard
    ) {}

    public function store(CreateShareRequest $request): JsonResponse
    {
        $alias = trim((string) $request->input('shareable_alias', ''));
        $type = trim((string) $request->input('shareable_type', ''));

        if ($alias !== '') {
            $mapped = config('share.aliases.'.$alias);
            if (is_string($mapped) && class_exists($mapped)) {
                $type = $mapped;
            }
        }

        if ($type === '' || ! class_exists($type)) {
            abort(422, 'Invalid shareable_type');
        }

        $id = (int) $request->input('shareable_id');
        $model = $type::query()->findOrFail($id);

        if (! $model instanceof Shareable) {
            abort(422, 'Model is not shareable');
        }

        /** @var \Illuminate\Database\Eloquent\Model&Shareable $model */
        $channel = ShareChannel::from($request->string('channel')->toString());

        if (! $this->guard->canShare($model, $request->user(), $channel)) {
            abort(403);
        }

        $share = $this->service->create(
            $model,
            $channel,
            $request->user()?->getKey(),
            [
                'utm_source' => $request->input('utm_source'),
                'utm_medium' => $request->input('utm_medium'),
                'utm_campaign' => $request->input('utm_campaign'),
            ],
            $request->date('expires_at'),
            (bool) $request->boolean('is_active', true)
        );

        return response()->json([
            'id' => $share->getKey(),
            'channel' => $share->channel,
            'short_code' => $share->short_code,
            'short_url' => $this->service->getShortUrl($share->short_code),
            'expires_at' => $share->expires_at?->toISOString(),
            'is_active' => $share->is_active,
            'utm_source' => $share->utm_source,
            'utm_medium' => $share->utm_medium,
            'utm_campaign' => $share->utm_campaign,
        ]);
    }
}
