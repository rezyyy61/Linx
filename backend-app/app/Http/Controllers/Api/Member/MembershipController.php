<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\MembershipRequest;
use App\Http\Resources\Member\MembershipResource;
use App\Models\Member\Membership;
use App\Models\User;
use App\Services\Member\MembershipService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MembershipController extends Controller
{
    public function __construct(protected MembershipService $service)
    {
        $this->authorizeResource(Membership::class, 'membership');
    }

    public function index(Request $request, User $user): AnonymousResourceCollection
    {
        $status = $request->get('status');
        $items = $this->service->listForOwner($user, $status);
        $items->load([
            'member.profile',
            'member.profile.logo',
            'member.profile.translations',
        ]);

        return MembershipResource::collection($items);
    }

    public function store(MembershipRequest $request, User $user): MembershipResource
    {
        $membership = $this->service->create($user, $request->validated());
        $membership->load(['member.profile', 'member.profile.logo', 'member.profile.translations']);

        return new MembershipResource($membership);
    }

    public function accept(Membership $membership): MembershipResource
    {
        $this->authorize('update', $membership);
        $membership = $this->service->accept($membership);
        $membership->load(['member.profile', 'member.profile.logo', 'member.profile.translations']);

        return new MembershipResource($membership);
    }

    public function reject(Request $request, Membership $membership): MembershipResource
    {
        $this->authorize('update', $membership);
        $membership = $this->service->reject($membership, $request->string('reason')->toString());
        $membership->load(['member.profile', 'member.profile.logo', 'member.profile.translations']);

        return new MembershipResource($membership);
    }

    public function indexAsMember(Request $request, User $user): AnonymousResourceCollection
    {
        $status = $request->get('status');
        $items = $this->service->listForMember($user, $status);
        $items->load([
            'owner.profile',
            'owner.profile.logo',
            'owner.profile.translations',
        ]);

        return MembershipResource::collection($items);
    }

    public function destroy(Membership $membership)
    {
        $this->authorize('delete', $membership);
        $membership->delete();

        return response()->noContent();
    }
}
