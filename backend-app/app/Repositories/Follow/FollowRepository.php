<?php

namespace App\Repositories\Follow;

use App\Models\Follow\Follow;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FollowRepository implements FollowRepositoryInterface
{
    public function create(int $followerId, int $followedId): void
    {
        Follow::query()->firstOrCreate([
            'follower_id' => $followerId,
            'followed_id' => $followedId,
        ]);
    }

    public function delete(int $followerId, int $followedId): int
    {
        return Follow::query()
            ->where('follower_id', $followerId)
            ->where('followed_id', $followedId)
            ->delete();
    }

    public function exists(int $followerId, int $followedId): bool
    {
        return Follow::query()
            ->where('follower_id', $followerId)
            ->where('followed_id', $followedId)
            ->exists();
    }

    public function followers(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $user->followers()->latest('follows.created_at')->paginate($perPage);
    }

    public function followings(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $user->followings()->latest('follows.created_at')->paginate($perPage);
    }

    public function suggestions(User $user, int $limit = 10)
    {
        return User::query()
            ->whereKeyNot($user->id)
            ->whereDoesntHave('followers', fn ($q) => $q->where('follower_id', $user->id))
            ->withCount(['followers', 'followings'])
            ->orderByDesc('followers_count')
            ->limit($limit)
            ->get();
    }
}
