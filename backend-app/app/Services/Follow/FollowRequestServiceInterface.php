<?php

namespace App\Services\Follow;

use App\Models\Follow\FollowRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

interface FollowRequestServiceInterface
{
    /** @return EloquentCollection<int, FollowRequest> */
    public function incoming(User $user): EloquentCollection;

    /** @return EloquentCollection<int, FollowRequest> */
    public function outgoing(User $user): EloquentCollection;

    public function create(User $actor, User $target): array;

    public function accept(FollowRequest $followRequest): void;

    public function reject(FollowRequest $followRequest): void;

    public function cancel(FollowRequest $followRequest): void;
}
