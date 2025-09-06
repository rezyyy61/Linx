<?php

namespace App\Repositories\Follow;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface FollowRepositoryInterface
{
    public function create(int $followerId, int $followedId): void;

    public function delete(int $followerId, int $followedId): int;

    public function followers(User $user, int $perPage = 15): LengthAwarePaginator;

    public function followings(User $user, int $perPage = 15): LengthAwarePaginator;

    public function exists(int $followerId, int $followedId): bool;

    public function suggestions(User $user, int $limit);
}
