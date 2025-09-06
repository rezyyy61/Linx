<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Campaign;

use App\Http\Controllers\Controller;
use App\Http\Requests\Campaign\ScheduleContentRequest;
use App\Http\Requests\Campaign\StoreContentRequest;
use App\Http\Requests\Campaign\UpdateContentRequest;
use App\Models\Campaign\Campaign;
use App\Models\Campaign\CampaignContent;
use App\Services\Campaign\ContentService;
use Illuminate\Http\JsonResponse;

class ContentController extends Controller
{
    public function __construct(private readonly ContentService $service)
    {
    }

    public function store(Campaign $campaign, StoreContentRequest $request): JsonResponse
    {
        $item = $this->service->create($campaign, $request->validated());
        return response()->json($item, 201);
    }

    public function update(Campaign $campaign, CampaignContent $content, UpdateContentRequest $request): JsonResponse
    {
        if ((int) $content->campaign_id !== (int) $campaign->id) {
            abort(404);
        }
        $item = $this->service->update($content, $request->validated());
        return response()->json($item);
    }

    public function schedule(Campaign $campaign, CampaignContent $content, ScheduleContentRequest $request): JsonResponse
    {
        if ((int) $content->campaign_id !== (int) $campaign->id) {
            abort(404);
        }
        $payload = $request->validated();
        $item = $this->service->schedule($content, (string) $payload["schedule_at"]);
        return response()->json($item);
    }
}
