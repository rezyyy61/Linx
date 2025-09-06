<?php

namespace App\Http\Controllers\Api\Follow;

use App\Http\Controllers\Controller;
use App\Http\Resources\Profile\ProfileLiteResource;
use App\Models\Follow\FollowRequest;
use App\Models\User;
use App\Services\Follow\FollowRequestServiceInterface;
use Illuminate\Http\Request;

class FollowRequestController extends Controller
{
    public function __construct(private FollowRequestServiceInterface $service)
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $items = $this->service->incoming($user)
            ->loadMissing(['actor.profile', 'actor.profile.logo', 'actor.profile.translations'])
            ->map(function (FollowRequest $fr) {
                $actor = $fr->actor;

                return [
                    'id' => $fr->id,
                    'actor_id' => $fr->actor_id,
                    'target_id' => $fr->target_id,
                    'status' => $fr->status,
                    'created_at' => $fr->created_at,
                    'actor' => new ProfileLiteResource($actor->profile),
                ];
            });

        return response()->json(['ok' => true, 'data' => $items]);
    }

    public function outgoing(Request $request)
    {
        $user = $request->user();

        $items = $this->service->outgoing($user)
            ->loadMissing(['target.profile', 'target.profile.logo', 'target.profile.translations'])
            ->map(function (FollowRequest $fr) {
                $target = $fr->target;

                return [
                    'id' => $fr->id,
                    'actor_id' => $fr->actor_id,
                    'target_id' => $fr->target_id,
                    'status' => $fr->status,
                    'created_at' => $fr->created_at,
                    'target' => new ProfileLiteResource($target->profile),
                ];
            });

        return response()->json(['ok' => true, 'data' => $items]);
    }

    public function store(Request $request, User $user)
    {
        $this->authorize('create', [FollowRequest::class, $user]);
        $actor = $request->user();

        $result = $this->service->create($actor, $user);

        if ($result['created']) {
            return response()->json(['ok' => true, 'data' => ['id' => $result['request']?->id]], 201);
        }

        return response()->json(['ok' => true], 200);
    }

    public function accept(Request $request, FollowRequest $followRequest)
    {
        $this->authorize('decide', $followRequest);
        $this->service->accept($followRequest);

        return response()->json(['ok' => true]);
    }

    public function reject(Request $request, FollowRequest $followRequest)
    {
        $this->authorize('decide', $followRequest);
        $this->service->reject($followRequest);

        return response()->json(['ok' => true]);
    }

    public function cancel(Request $request, FollowRequest $followRequest)
    {
        $this->authorize('cancel', $followRequest);
        $this->service->cancel($followRequest);

        return response()->json(['ok' => true]);
    }
}
