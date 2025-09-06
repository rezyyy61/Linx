<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Campaign;

use App\Http\Controllers\Controller;
use App\Http\Requests\Campaign\DonateRequest;
use App\Models\Campaign\Campaign;
use App\Services\Campaign\DonationService;
use Illuminate\Http\JsonResponse;

class DonationController extends Controller
{
    public function __construct(private readonly DonationService $service)
    {
    }

    public function store(Campaign $campaign, DonateRequest $request): JsonResponse
    {
        $donation = $this->service->create($campaign, $request->validated());
        return response()->json($donation, 201);
    }
}
