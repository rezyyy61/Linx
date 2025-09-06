<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Campaign;

use App\Http\Controllers\Controller;
use App\Http\Requests\Campaign\StoreCampaignRequest;
use App\Http\Requests\Campaign\UpdateCampaignRequest;
use App\Models\Campaign\Campaign;
use App\Services\Campaign\CampaignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function __construct(private readonly CampaignService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) ($request->integer('per_page') ?: 15);
        return response()->json($this->service->list($perPage));
    }

    public function store(StoreCampaignRequest $request): JsonResponse
    {
        $data = $request->validated();
        if ($request->user()) {
            $data['owner_id'] = $request->user()->id;
        }
        $campaign = $this->service->create($data);
        return response()->json($campaign, 201);
    }

    public function show(Campaign $campaign): JsonResponse
    {
        return response()->json($campaign);
    }

    public function update(UpdateCampaignRequest $request, Campaign $campaign): JsonResponse
    {
        $updated = $this->service->update($campaign, $request->validated());
        return response()->json($updated);
    }
}
