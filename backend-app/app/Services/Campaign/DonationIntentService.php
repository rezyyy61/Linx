<?php

namespace App\Services\Campaign;

use App\Models\Campaign\Campaign;
use App\Models\Campaign\DonationIntent;
use App\Services\Campaign\Exceptions\NotBelongsToCampaignException;

class DonationIntentService implements DonationIntentServiceInterface
{
    public function createPublicIntent(Campaign $campaign, string $email, float $amount, ?string $currency = 'USD', ?string $message = null): DonationIntent
    {
        if (! $campaign->donation_enabled) {
            abort(422, 'Donations are disabled for this campaign');
        }

        $intent = new DonationIntent([
            'requester_email' => $email,
            'amount' => $amount,
            'currency' => strtoupper($currency ?: 'USD'),
            'message' => $message,
            'status' => 'pending',
            'token' => (string) \Illuminate\Support\Str::uuid(),
            'meta' => ['mail_queued' => false],
        ]);

        $intent->campaign()->associate($campaign);
        $intent->save();

        return $intent;
    }

    public function scheduleEmail(Campaign $campaign, DonationIntent $intent): DonationIntent
    {
        $this->assertBelongs($campaign, $intent);

        $meta = $intent->meta ?? [];
        $meta['mail_queued'] = true;

        $intent->meta = $meta;
        $intent->save();

        return $intent;
    }

    public function markPaid(Campaign $campaign, DonationIntent $intent): DonationIntent
    {
        $this->assertBelongs($campaign, $intent);

        $intent->status = 'paid';
        $intent->paid_at = now();
        $intent->save();

        return $intent;
    }

    private function assertBelongs(Campaign $campaign, DonationIntent $intent): void
    {
        if ($intent->campaign_id !== $campaign->id) {
            throw new NotBelongsToCampaignException('DonationIntent does not belong to campaign');
        }
    }
}
