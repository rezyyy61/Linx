<?php

namespace App\Services\Campaign;

use App\Models\Campaign\Campaign;
use App\Models\Campaign\DonationIntent;

interface DonationIntentServiceInterface
{
    public function createPublicIntent(Campaign $campaign, string $email, float $amount, ?string $currency = 'USD', ?string $message = null): DonationIntent;

    public function scheduleEmail(Campaign $campaign, DonationIntent $intent): DonationIntent;

    public function markPaid(Campaign $campaign, DonationIntent $intent): DonationIntent;
}
