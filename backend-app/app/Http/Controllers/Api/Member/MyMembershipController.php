<?php

namespace App\Http\Controllers\Api\Member;

use App\Enums\MembershipStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Member\MembershipResource;
use App\Models\Member\Membership;
use App\Models\User;
use App\Services\Member\MyMembershipService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MyMembershipController extends Controller
{
    public function __construct(protected MyMembershipService $service)
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request, User $user): AnonymousResourceCollection
    {
        $statusParam = $request->get('status');
        $status = null;
        if ($statusParam !== null) {
            $status = MembershipStatus::tryFrom($statusParam);
        }

        $items = $this->service->listFor($user, $status);
        $items->load([
            'owner.profile',
            'owner.profile.logo',
            'owner.profile.translations',
        ]);

        return MembershipResource::collection($items);
    }

    public function update(Request $request, Membership $membership): MembershipResource
    {
        abort_if($request->user()->id !== $membership->member_id, 403);

        $data = $request->validate([
            'meta' => ['array'],
            'meta.notifications' => ['array'],
            'meta.notifications.muted' => ['boolean'],
            'meta.notifications.digest' => ['in:immediate,daily,weekly'],
            'meta.notifications.channels' => ['array'],
            'meta.notifications.channels.*' => ['in:email,sms,push'],
            'meta.pinned' => ['boolean'],
        ]);

        $membership = $this->service->updateMeta($membership, $data['meta'] ?? []);
        $membership->load(['owner.profile', 'owner.profile.logo', 'owner.profile.translations']);

        return new MembershipResource($membership);
    }

    public function leave(Request $request, Membership $membership): MembershipResource
    {
        abort_if($request->user()->id !== $membership->member_id, 403);

        $reason = $request->string('reason')->toString() ?: null;
        $membership = $this->service->leave($membership, $reason);
        $membership->load(['owner.profile', 'owner.profile.logo', 'owner.profile.translations']);

        return new MembershipResource($membership);
    }
}
