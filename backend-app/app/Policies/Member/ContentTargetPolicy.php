<?php

namespace App\Policies\Member;

use App\Models\Member\MemberContentTarget;
use App\Models\User;

class ContentTargetPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MemberContentTarget $target): bool
    {
        return $target->content?->owner_id === $user->id;
    }

    public function update(User $user, MemberContentTarget $target): bool
    {
        return $target->content?->owner_id === $user->id;
    }

    public function delete(User $user, MemberContentTarget $target): bool
    {
        return $target->content?->owner_id === $user->id;
    }
}
