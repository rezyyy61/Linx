<?php

namespace App\Policies\Member;

use App\Models\Member\MemberContent;
use App\Models\User;

class ContentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MemberContent $content): bool
    {
        return $content->owner_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, MemberContent $content): bool
    {
        return $content->owner_id === $user->id;
    }

    public function delete(User $user, MemberContent $content): bool
    {
        return $content->owner_id === $user->id;
    }
}
