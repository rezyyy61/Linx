<?php

namespace App\Models\Concerns;

use App\Models\User;

trait Followable
{
    public function follow(User $user): bool
    {
        if ($this->id === $user->id) {
            return false;
        }

        $this->followings()->syncWithoutDetaching([$user->id]);

        return true;
    }

    public function unfollow(User $user): int
    {
        return $this->followings()->detach($user->id);
    }

    public function isFollowing(User $user): bool
    {
        return $this->followings()->where('users.id', $user->id)->exists();
    }

    public function isFollowedBy(User $user): bool
    {
        return $this->followers()->where('users.id', $user->id)->exists();
    }
}
