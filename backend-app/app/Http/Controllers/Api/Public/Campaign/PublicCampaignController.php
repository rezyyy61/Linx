<?php

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
        $filters = $request->validate([
            'q' => ['nullable', 'string'],
            'kind' => ['nullable', 'in:fundraising,petition,volunteer,awareness'],
            'date_range' => ['nullable', 'in:all,today,this_week,this_month'],
            'order_by' => ['nullable', 'in:publish_at,starts_at,created_at,updated_at,title'],
            'order_dir' => ['nullable', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'status' => ['nullable', 'in:published'],
            'visibility' => ['nullable', 'in:public,members,private'],
        ]);

        $items = $this->service->list($filters);

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
