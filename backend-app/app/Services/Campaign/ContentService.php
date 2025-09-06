<?php

declare(strict_types=1);

namespace App\Services\Campaign;

use App\Models\Campaign\Campaign;
use App\Models\Campaign\CampaignContent;

class ContentService
{
    public function create(Campaign $campaign, array $data): CampaignContent
    {
        $data["campaign_id"] = $campaign->id;
        return CampaignContent::query()->create($data);
    }

    public function update(CampaignContent $content, array $data): CampaignContent
    {
        $content->update($data);
        return $content;
    }

    public function schedule(CampaignContent $content, string $at): CampaignContent
    {
        $content->schedule_at = \Illuminate\Support\Carbon::parse($at);
        $content->status = "scheduled";
        $content->save();
        return $content;
    }
}
