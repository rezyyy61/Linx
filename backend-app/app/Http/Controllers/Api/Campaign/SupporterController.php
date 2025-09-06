<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Campaign;

use App\Http\Controllers\Controller;
use App\Http\Requests\Campaign\FollowRequest;
use App\Models\Campaign\Campaign;
use App\Services\Campaign\SupporterService;
use Illuminate\Http\JsonResponse;

class SupporterController extends Controller
{
    public function __construct(private readonly SupporterService $service)
    {
    }

    public function follow(Campaign $campaign, FollowRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (!isset($data['user_id']) && $request->user()) {
            $data['user_id'] = $request->user()->id;
        }
        $supporter = $this->service->follow($campaign, $data);
        return response()->json($supporter, 201);
    }
}
