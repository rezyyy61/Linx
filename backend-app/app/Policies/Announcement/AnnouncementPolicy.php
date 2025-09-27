<?php

declare(strict_types=1);

namespace App\Policies\Announcement;

use App\Models\Announcement\Announcement;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AnnouncementPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Announcement $announcement): bool
    {
        if ($announcement->visibility === 'public') {
            return true;
        }

        return (int) $announcement->owner_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Announcement $announcement): bool
    {
        return (int) $announcement->owner_id === (int) $user->id;
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return (int) $announcement->owner_id === (int) $user->id;
    }

    public function restore(User $user, Announcement $announcement): bool
    {
        return (int) $announcement->owner_id === (int) $user->id;
    }

    public function forceDelete(User $user, Announcement $announcement): bool
    {
        return false;
    }
}
