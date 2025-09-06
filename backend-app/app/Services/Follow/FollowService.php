<?php

namespace App\Services\Follow;

use App\Events\Follow\FriendRemoved;
use App\Models\User;
use App\Repositories\Follow\FollowRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FollowService implements FollowServiceInterface
{
    public function __construct(private FollowRepositoryInterface $repo) {}

    public function follow(User $actor, User $target): void
    {
        if ($actor->id === $target->id) {
            throw ValidationException::withMessages(['user' => ['Cannot follow yourself.']]);
        }

        if ($this->repo->exists($actor->id, $target->id)) {
            return;
        }

        $this->repo->create($actor->id, $target->id);
    }

    public function unfollow(User $actor, User $target): void
    {
        DB::transaction(function () use ($actor, $target) {
            $this->repo->delete($actor->id, $target->id);
            $this->repo->delete($target->id, $actor->id);

            event(new FriendRemoved($actor->id, $target->id));
            event(new FriendRemoved($target->id, $actor->id));
        });
    }

    public function followers(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repo->followers($user, $perPage);
    }

    public function followings(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repo->followings($user, $perPage);
    }

    public function suggestions(User $user, int $limit = 10)
    {
        return $this->repo->suggestions($user, $limit);
    }
}
