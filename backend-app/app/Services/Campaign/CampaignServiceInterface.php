<?php

namespace App\Services\Campaign;

use App\Models\Campaign\Campaign;

interface CampaignServiceInterface
{
    public function create(array $data, int $ownerId): Campaign;

    public function update(Campaign $campaign, array $data): Campaign;

    public function delete(Campaign $campaign): void;
}
