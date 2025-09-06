<?php

namespace App\Policies\Follow;

use App\Models\Follow\FollowRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FollowRequestPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return (bool) $user->id;
    }

    public function create(User $auth, User $target): bool
    {
        return $auth->id && $target->id && $auth->id !== $target->id;
    }

    public function decide(User $user, FollowRequest $request): bool
    {
        return $user->id === $request->target_id;
    }
}
