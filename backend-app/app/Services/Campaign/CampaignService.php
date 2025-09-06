<?php

declare(strict_types=1);

namespace App\Services\Campaign;

use App\Models\Campaign\Campaign;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CampaignService
{
    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return Campaign::query()->latest('id')->paginate($perPage);
    }

    public function create(array $data): Campaign
    {
        if (!isset($data['status'])) {
            $data['status'] = 'draft';
        }
        return Campaign::query()->create($data);
    }

    public function update(Campaign $campaign, array $data): Campaign
    {
        $campaign->update($data);
        return $campaign;
    }
}
