<?php

declare(strict_types=1);

namespace App\Policies\Campaign;

use App\Models\Campaign\Campaign;
use App\Models\User;

class CampaignPolicy
{
    public function view(?User $user, Campaign $campaign): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Campaign $campaign): bool
    {
        return (int) $campaign->owner_id === (int) $user->id;
    }
}
