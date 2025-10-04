<?php

namespace App\Http\Controllers\Api\Campaign;

use App\Http\Controllers\Controller;
use App\Http\Requests\Campaign\CampaignIndexRequest;
use App\Http\Requests\Campaign\CampaignStoreRequest;
use App\Http\Requests\Campaign\CampaignUpdateRequest;
use App\Http\Resources\Campaign\CampaignResource;
use App\Models\Campaign\Campaign;
use App\Services\Campaign\CampaignService;
use App\Services\Campaign\DTOs\CreateCampaignData;
use App\Services\Campaign\DTOs\UpdateCampaignData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CampaignController extends Controller
{
    public function __construct(private CampaignService $service) {}

    public function index(CampaignIndexRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();
        $perPage = (int) ($filters['per_page'] ?? 15);
        $orderBy = $filters['order_by'] ?? 'publish_at';
        $orderDir = $filters['order_dir'] ?? 'desc';

        $user = $request->user();
        if (! ($user && $user->can('campaign.viewAny'))) {
            $filters['owner_id'] = $user?->id;
        }

        $paginator = $this->service->list($filters, $perPage, $orderBy, $orderDir);

        return CampaignResource::collection($paginator);
    }

    public function store(CampaignStoreRequest $request): CampaignResource
    {
        $v = $request->validated();
        $dto = new CreateCampaignData(...$v);
        $campaign = $this->service->create($dto);

        return new CampaignResource($campaign);
    }

    public function show(Campaign $campaign): CampaignResource
    {
        $campaign->load(['owner', 'covers', 'documents']);

        return new CampaignResource($campaign);
    }

    public function update(CampaignUpdateRequest $request, Campaign $campaign): CampaignResource
    {
        $v = $request->validated();
        $dto = new UpdateCampaignData(...$v);
        $updated = $this->service->update($campaign, $dto);

        return new CampaignResource($updated);
    }

    public function destroy(Campaign $campaign): JsonResponse
    {
        $this->service->delete($campaign);

        return response()->json(['success' => true]);
    }

    public function publish(Campaign $campaign): CampaignResource
    {
        $published = $this->service->publish($campaign);

        return new CampaignResource($published);
    }
}
