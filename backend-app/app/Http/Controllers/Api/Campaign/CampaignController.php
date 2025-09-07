<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Campaign;

use App\Http\Controllers\Controller;
use App\Http\Requests\Campaign\ListCampaignRequest;
use App\Http\Requests\Campaign\StoreCampaignRequest;
use App\Http\Requests\Campaign\UpdateCampaignRequest;
use App\Http\Resources\Campaign\CampaignResource;
use App\Models\Campaign\Campaign;
use App\Services\Campaign\CampaignService;

class CampaignController extends Controller
{
    public function __construct(private CampaignService $service)
    {
        $this->authorizeResource(Campaign::class, 'campaign');
    }

    public function index(ListCampaignRequest $request)
    {
        $paginated = $this->service->list($request->validated());

        return response()->json([
            'data' => CampaignResource::collection($paginated->getCollection()),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ],
        ]);
    }

    public function store(StoreCampaignRequest $request)
    {
        $id = $this->service->create($request->validated());
        $campaign = Campaign::with(['covers', 'documents'])->findOrFail($id);

        return (new CampaignResource($campaign))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Campaign $campaign)
    {
        $campaign->loadMissing(['covers', 'documents']);

        return new CampaignResource($campaign);
    }

    public function update(UpdateCampaignRequest $request, Campaign $campaign)
    {
        $campaign = $this->service->update($campaign, $request->validated());
        $campaign->loadMissing(['covers', 'documents']);

        return new CampaignResource($campaign);
    }

    public function destroy(Campaign $campaign)
    {
        $this->service->delete($campaign);

        return response()->json([], 204);
    }
}
