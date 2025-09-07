<?php

namespace App\Policies\Announcement;

use App\Models\Campaign\Campaign;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AnnouncementPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Campaign $campaign): bool
    {
        return $user->id === $campaign->owner_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Campaign $campaign): bool
    {
        return $user->id === $campaign->owner_id;
    }

    public function delete(User $user, Campaign $campaign): bool
    {
        return $user->id === $campaign->owner_id;
    }

    public function restore(User $user, Campaign $campaign): bool
    {
        return $user->id === $campaign->owner_id;
    }

    public function forceDelete(User $user, Campaign $campaign): bool
    {
        return false;
    }
}
