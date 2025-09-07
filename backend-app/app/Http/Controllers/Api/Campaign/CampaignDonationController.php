<?php

namespace App\Http\Controllers\Api\Campaign;

use App\Http\Controllers\Controller;
use App\Models\Campaign\Campaign;
use App\Models\Campaign\DonationIntent;
use App\Services\Campaign\DonationIntentServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignDonationController extends Controller
{
    public function __construct(private readonly DonationIntentServiceInterface $service) {}

    public function index(Request $request, Campaign $campaign): JsonResponse
    {
        $this->authorize('manageDonations', $campaign);

        $items = $campaign->donationIntents()->latest('id')->paginate(20);

        return response()->json($items);
    }

    public function markPaid(Request $request, Campaign $campaign, DonationIntent $intent): JsonResponse
    {
        $this->authorize('manageDonations', $campaign);

        $intent = $this->service->markPaid($campaign, $intent);

        return response()->json($intent);
    }

    public function scheduleEmail(Request $request, Campaign $campaign, DonationIntent $intent): JsonResponse
    {
        $this->authorize('manageDonations', $campaign);

        $intent = $this->service->scheduleEmail($campaign, $intent);

        return response()->json($intent);
    }
}
