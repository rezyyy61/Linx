<?php

namespace App\Policies\Member;

use App\Models\Member\Membership;
use App\Models\User;

class MembershipPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Membership $membership): bool
    {
        return $membership->owner_id === $user->id || $membership->member_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Membership $membership): bool
    {
        return $membership->owner_id === $user->id || $membership->member_id === $user->id;
    }

    public function delete(User $user, Membership $membership): bool
    {
        return $membership->owner_id === $user->id;
    }
}
