<?php

namespace App\Policies\Follow;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FollowPolicy
{
    use HandlesAuthorization;

    public function follow(User $auth, User $target): bool
    {
        if (! $auth->id || ! $target->id) {
            return false;
        }

        if ($auth->id === $target->id) {
            return false;
        }

        return true;
    }

    public function unfollow(User $auth, User $target): bool
    {
        if (! $auth->id || ! $target->id) {
            return false;
        }

        if ($auth->id === $target->id) {
            return false;
        }

        return true;
    }
}
