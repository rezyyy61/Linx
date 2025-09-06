<?php

namespace App\Services\Follow;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface FollowServiceInterface
{
    public function follow(User $actor, User $target): void;

    public function unfollow(User $actor, User $target): void;

    public function followers(User $user, int $perPage = 15): LengthAwarePaginator;

    public function followings(User $user, int $perPage = 15): LengthAwarePaginator;

    public function suggestions(User $user, int $limit = 10);
}
