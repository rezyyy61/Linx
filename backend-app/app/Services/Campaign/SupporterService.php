<?php

declare(strict_types=1);

namespace App\Services\Campaign;

use App\Models\Campaign\Campaign;
use App\Models\Campaign\CampaignSupporter;

class SupporterService
{
    public function follow(Campaign $campaign, array $data): CampaignSupporter
    {
        $attrs = [
            'role' => $data['role'] ?? 'follower',
            'contact_email' => $data['contact_email'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'tags_json' => $data['tags_json'] ?? null,
            'consent_at' => now(),
        ];

        if (isset($data['user_id'])) {
            return CampaignSupporter::query()->updateOrCreate(
                ['campaign_id' => $campaign->id, 'user_id' => $data['user_id']],
                $attrs
            );
        }

        if (isset($data['contact_email'])) {
            return CampaignSupporter::query()->updateOrCreate(
                ['campaign_id' => $campaign->id, 'contact_email' => $data['contact_email']],
                $attrs
            );
        }

        return CampaignSupporter::query()->create(array_merge(['campaign_id' => $campaign->id], $attrs));
    }
}
