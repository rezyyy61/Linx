<?php

namespace App\Services\Member;

use App\Enums\MembershipStatus;
use App\Http\Resources\Profile\ProfileLiteResource;
use App\Models\Member\Membership;
use App\Models\User;
use App\Notifications\Member\MembershipAccepted;
use App\Notifications\Member\MembershipInvited;
use App\Notifications\Member\MembershipRejected;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class MembershipService
{
    public function create(User $owner, array $data): Membership
    {
        $memberId = $data['member_id'] ?? null;

        $existing = Membership::where('owner_id', $owner->id)
            ->where('member_id', $memberId)
            ->first();

        if ($existing) {
            return $existing;
        }

        $membership = Membership::create([
            'owner_id' => $owner->id,
            'member_id' => $memberId,
            'email' => $data['email'] ?? null,
            'contact_info' => $data['contact_info'] ?? null,
            'meta' => $data['meta'] ?? null,
            'status' => MembershipStatus::PENDING,
        ]);

        if ($memberId) {
            $owner->loadMissing(['profile', 'profile.logo', 'profile.translations']);
            $actorLite = (new ProfileLiteResource($owner->profile))->toArray(request());
            $target = User::find($memberId);
            if ($target) {
                $frontend = rtrim((string) config('app.frontend_url', ''), '/');
                $url = $frontend ? $frontend.'/dashboard/audience/members?tab=requests' : null;
                $target->notify(new MembershipInvited($actorLite, $membership->id, $url));
            }
        }

        return $membership;
    }

    public function accept(Membership $membership): Membership
    {
        $membership->update([
            'status' => MembershipStatus::ACCEPTED,
            'consent_at' => now(),
        ]);

        $member = $membership->member;
        $owner = $membership->owner;

        if ($member && $owner) {
            $member->loadMissing(['profile', 'profile.logo', 'profile.translations']);
            $actorLite = (new ProfileLiteResource($member->profile))->toArray(request());
            $frontend = rtrim((string) config('app.frontend_url', ''), '/');
            $url = $frontend ? $frontend.'/dashboard/audience/members' : null;
            $owner->notify(new MembershipAccepted($actorLite, $membership->id, $url));
        }

        return $membership;
    }

    public function reject(Membership $membership, ?string $reason = null): Membership
    {
        $membership->update([
            'status' => MembershipStatus::REJECTED,
            'rejected_reason' => $reason,
        ]);

        $member = $membership->member;
        $owner = $membership->owner;

        if ($member && $owner) {
            $member->loadMissing(['profile', 'profile.logo', 'profile.translations']);
            $actorLite = (new ProfileLiteResource($member->profile))->toArray(request());
            $frontend = rtrim((string) config('app.frontend_url', ''), '/');
            $url = $frontend ? $frontend.'/dashboard/audience/members' : null;
            $owner->notify(new MembershipRejected($actorLite, $membership->id, $url));
        }

        return $membership;
    }

    public function block(Membership $membership): Membership
    {
        $membership->update([
            'status' => MembershipStatus::BLOCKED,
        ]);

        return $membership;
    }

    /** @return EloquentCollection<int,\App\Models\Member\Membership> */
    public function listForOwner(User $owner, ?MembershipStatus $status = null): EloquentCollection
    {
        $q = Membership::forOwner($owner->id);
        if ($status) {
            $q->where('status', $status);
        }

        return $q->get();
    }

    public function listForMember(User $member, ?MembershipStatus $status = null): EloquentCollection
    {
        $q = Membership::query()->where('member_id', $member->id);
        if ($status) {
            $q->where('status', $status);
        }

        return $q->get();
    }
}
