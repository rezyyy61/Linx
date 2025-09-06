<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Campaign;

use App\Http\Controllers\Controller;
use App\Models\Campaign\Campaign;
use App\Services\Campaign\StatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function __construct(private readonly StatsService $service)
    {
    }

    public function show(Request $request, Campaign $campaign): JsonResponse
    {
        $data = $this->service->forCampaign(
            $campaign,
            $request->query('from'),
            $request->query('to')
        );
        return response()->json($data);
    }
}
