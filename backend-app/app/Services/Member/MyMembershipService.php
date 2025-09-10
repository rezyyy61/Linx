<?php

namespace App\Services\Member;

use App\Enums\MembershipStatus;
use App\Models\Member\Membership;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class MyMembershipService
{
    /** @return EloquentCollection<int,Membership> */
    public function listFor(User $member, ?MembershipStatus $status = null): EloquentCollection
    {
        $q = Membership::query()
            ->where('member_id', $member->id);

        if ($status) {
            $q->where('status', $status);
        }

        return $q->get();
    }

    public function updateMeta(Membership $membership, array $meta): Membership
    {
        $merged = array_replace_recursive((array) ($membership->meta ?? []), $meta);

        $membership->meta = $merged;
        $membership->save();

        return $membership;
    }

    public function leave(Membership $membership, ?string $reason = null): Membership
    {
        $membership->status = MembershipStatus::REJECTED;
        $membership->rejected_reason = $reason ?: 'left_by_member';
        $membership->save();

        return $membership;
    }
}
