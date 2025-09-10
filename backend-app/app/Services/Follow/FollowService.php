<?php

namespace App\Services\Follow;

use App\Events\Follow\FriendRemoved;
use App\Http\Resources\Profile\ProfileLiteResource;
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

            $actor->loadMissing(['profile', 'profile.logo', 'profile.translations']);
            $actorLite = (new ProfileLiteResource($actor->profile))->toArray(request());

            $target->notify(new \App\Notifications\Follow\Unfollowed([
                'id' => $actor->id,
                'slug' => $actorLite['slug'] ?? null,
                'username' => $actor->username ?? null,
                'avatar' => $actorLite['avatar'] ?? null,
            ]));

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

    public function suggestions(User $me, int $limit = 10)
    {
        $meId = (int) $me->id;

        return User::query()
            ->where('users.id', '!=', $meId)
            ->whereNotExists(function ($q) use ($meId) {
                $q->from('follows')
                    ->whereColumn('follows.followed_id', 'users.id')
                    ->where('follows.follower_id', $meId);
            })
            ->whereNotExists(function ($q) use ($meId) {
                $q->from('follows')
                    ->whereColumn('follows.follower_id', 'users.id')
                    ->where('follows.followed_id', $meId);
            })
            ->select('users.*')
            ->selectSub(function ($q) use ($meId) {
                $q->from('users as f')
                    ->whereExists(function ($q) use ($meId) {
                        $q->from('follows')
                            ->where('follows.follower_id', $meId)
                            ->whereColumn('follows.followed_id', 'f.id');
                    })
                    ->whereExists(function ($q) use ($meId) {
                        $q->from('follows')
                            ->whereColumn('follows.follower_id', 'f.id')
                            ->where('follows.followed_id', $meId);
                    })
                    ->whereExists(function ($q) {
                        $q->from('follows')
                            ->whereColumn('follows.follower_id', 'users.id')
                            ->whereColumn('follows.followed_id', 'f.id');
                    })
                    ->whereExists(function ($q) {
                        $q->from('follows')
                            ->whereColumn('follows.follower_id', 'f.id')
                            ->whereColumn('follows.followed_id', 'users.id');
                    })
                    ->selectRaw('COUNT(*)');
            }, 'mutual_count')
            ->having('mutual_count', '>', 0)
            ->orderByDesc('mutual_count')
            ->orderByDesc('users.id')
            ->with(['profile', 'profile.logo', 'profile.translations'])
            ->limit($limit)
            ->get();
    }

    public function mutuals(User $a, User $b, int $perPage = 15): LengthAwarePaginator
    {
        $aid = (int) $a->id;
        $bid = (int) $b->id;

        $q = User::query()
            ->whereKeyNot($aid)
            ->whereKeyNot($bid)
            ->whereHas('profile')
            ->whereExists(fn ($q) => $q->from('follows')
                ->whereColumn('follows.followed_id', 'users.id')
                ->where('follows.follower_id', $aid))
            ->whereExists(fn ($q) => $q->from('follows')
                ->whereColumn('follows.followed_id', 'users.id')
                ->where('follows.follower_id', $bid))
            ->with(['profile', 'profile.logo', 'profile.translations'])
            ->orderByDesc('users.id');

        return $q->paginate($perPage)->through(fn (User $u) => $u->getRelation('profile'));
    }
}
