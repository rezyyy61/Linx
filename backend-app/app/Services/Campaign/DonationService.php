<?php

declare(strict_types=1);

namespace App\Services\Campaign;

use App\Models\Campaign\Campaign;
use App\Models\Campaign\Donation;

class DonationService
{
    public function create(Campaign $campaign, array $data): Donation
    {
        $payload = [
            'campaign_id' => $campaign->id,
            'supporter_id' => $data['supporter_id'] ?? null,
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'USD',
            'provider' => $data['provider'] ?? null,
            'provider_ref' => $data['provider_ref'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'paid_at' => $data['paid_at'] ?? null,
            'meta' => $data['meta'] ?? null,
        ];

        return Donation::query()->create($payload);
    }
}
