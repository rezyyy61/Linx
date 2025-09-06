<?php

namespace App\Services\Follow;

use App\Events\Follow\FollowRequestAccepted;
use App\Events\Follow\FollowRequestCancelled;
use App\Events\Follow\FollowRequestCreated;
use App\Events\Follow\FollowRequestRejected;
use App\Events\Follow\FriendAdded;
use App\Http\Resources\Profile\ProfileLiteResource;
use App\Models\Follow\FollowRequest;
use App\Models\User;
use App\Notifications\Follow\Accepted;
use App\Notifications\Follow\Rejected;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FollowRequestService implements FollowRequestServiceInterface
{
    public function __construct(private FollowServiceInterface $followService) {}

    protected function lite(User $user): array
    {
        $user->loadMissing(['profile', 'profile.logo', 'profile.translations']);

        return (new ProfileLiteResource($user->profile))->toArray(request());
    }

    public function create(User $actor, User $target): array
    {
        if ($actor->id === $target->id) {
            throw ValidationException::withMessages(['user' => ['Cannot follow yourself.']]);
        }

        $alreadyFollowing = DB::table('follows')
            ->where('follower_id', $actor->id)
            ->where('followed_id', $target->id)
            ->exists();

        if ($alreadyFollowing) {
            return ['request' => null, 'created' => false];
        }

        $existsPendingEitherSide = FollowRequest::between($actor->id, $target->id)->pending()->exists();
        if ($existsPendingEitherSide) {
            $existing = FollowRequest::between($actor->id, $target->id)->pending()->first();

            return ['request' => $existing, 'created' => false];
        }

        $request = FollowRequest::create([
            'actor_id' => $actor->id,
            'target_id' => $target->id,
            'status' => 'pending',
        ]);

        $actor->loadMissing(['profile', 'profile.logo', 'profile.translations']);
        $actorLite = (new ProfileLiteResource($actor->profile))->toArray(request());

        event(new FollowRequestCreated(
            $request->id,
            $request->actor_id,
            $request->target_id,
            $actorLite
        ));

        return ['request' => $request, 'created' => true];
    }

    public function accept(FollowRequest $request): void
    {
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages(['request' => ['Not pending.']]);
        }

        DB::transaction(function () use ($request) {
            $request->loadMissing(['actor', 'target', 'target.profile', 'target.profile.logo', 'target.profile.translations']);
            $request->update(['status' => 'accepted', 'decided_at' => now()]);

            $this->followService->follow($request->actor, $request->target);
            $this->followService->follow($request->target, $request->actor);

            event(new FollowRequestAccepted(
                $request->id,
                $request->actor_id,
                $request->target_id,
                $this->lite($request->target)
            ));

            event(new FriendAdded($request->actor_id, $this->lite($request->target)));
            event(new FriendAdded($request->target_id, $this->lite($request->actor)));

            $targetLite = $this->lite($request->target);
            $request->actor->notify(new Accepted([
                'id' => $request->target_id,
                'slug' => $targetLite['slug'] ?? null,
                'username' => $request->target->username ?? null,
                'avatar' => $targetLite['avatar'] ?? null,
            ]));
        });
    }

    public function reject(FollowRequest $request): void
    {
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages(['request' => ['Not pending.']]);
        }

        $request->loadMissing(['target', 'target.profile', 'target.profile.logo', 'target.profile.translations']);
        $request->update(['status' => 'rejected', 'decided_at' => now()]);

        event(new FollowRequestRejected(
            $request->id,
            $request->actor_id,
            $request->target_id,
        ));

        $targetLite = $this->lite($request->target);
        $userActor = User::find($request->actor_id);
        if ($userActor) {
            $userActor->notify(new Rejected([
                'id' => $request->target_id,
                'slug' => $targetLite['slug'] ?? null,
                'username' => $request->target->username ?? null,
                'avatar' => $targetLite['avatar'] ?? null,
            ]));
        }
    }

    public function cancel(FollowRequest $request): void
    {
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages(['request' => ['Not pending.']]);
        }
        if ((int) $request->actor_id !== (int) auth()->id()) {
            throw ValidationException::withMessages(['request' => ['Not allowed.']]);
        }

        $request->loadMissing(['actor']);
        $request->update(['status' => 'rejected', 'decided_at' => now()]);

        event(new FollowRequestCancelled(
            $request->id,
            $request->actor_id,
            $request->target_id,
        ));
    }

    public function incoming(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return FollowRequest::query()
            ->where('target_id', $user->id)
            ->where('status', 'pending')
            ->latest()
            ->get();
    }

    public function outgoing(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return FollowRequest::query()
            ->where('actor_id', $user->id)
            ->where('status', 'pending')
            ->latest()
            ->get();
    }
}
