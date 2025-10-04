<?php

// app/Http/Controllers/Api/Public/Campaign/PublicCampaignController.php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Public\Campaign;

use App\Http\Controllers\Controller;
use App\Http\Resources\Campaign\CampaignResource;
use App\Services\Campaign\PublicCampaignService;
use Illuminate\Http\Request;

class PublicCampaignController extends Controller
{
    public function __construct(private PublicCampaignService $service) {}

    public function index(Request $request)
    {
        $items = $this->service->list([
            'q' => $request->string('q')->toString(),
            'kind' => $request->input('kind'),
            'starts_from' => $request->input('starts_from'),
            'starts_to' => $request->input('starts_to'),
            'order_by' => $request->input('order_by'),
            'order_dir' => $request->input('order_dir'),
            'per_page' => $request->integer('per_page', 15),
        ]);

        return CampaignResource::collection($items);
    }

    public function show(string $slug)
    {
        $campaign = $this->service->findBySlug($slug);
        if (! $campaign) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return new CampaignResource($campaign);
    }
}
