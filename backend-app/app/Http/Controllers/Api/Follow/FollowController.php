<?php

namespace App\Http\Controllers\Api\Follow;

use App\Http\Controllers\Controller;
use App\Http\Requests\Follow\FollowDestroyRequest;
use App\Http\Requests\Follow\FollowStoreRequest;
use App\Http\Resources\Profile\ProfileLiteResource;
use App\Models\User;
use App\Services\Follow\FollowServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FollowController extends Controller
{
    public function __construct(private FollowServiceInterface $service)
    {
        $this->middleware('auth:sanctum')->only(['store', 'destroy', 'mutuals']);
    }

    public function store(FollowStoreRequest $request, User $user)
    {
        $this->service->follow($request->user(), $user);

        return response()->json(['ok' => true], 201);
    }

    public function destroy(FollowDestroyRequest $request, User $user)
    {
        $this->service->unfollow($request->user(), $user);

        return response()->json(['ok' => true]);
    }

    public function followers(Request $request, User $user): AnonymousResourceCollection
    {
        $perPage = (int) $request->integer('per_page', 15);

        $paginator = $user->followers()
            ->with(['profile', 'profile.logo', 'profile.translations'])
            ->paginate($perPage)
            ->through(static function ($model) {
                return $model->getRelation('profile');
            });

        return ProfileLiteResource::collection($paginator);
    }

    public function followings(Request $request, User $user): AnonymousResourceCollection
    {
        $perPage = (int) $request->integer('per_page', 15);

        $paginator = $user->followings()
            ->with(['profile', 'profile.logo', 'profile.translations'])
            ->paginate($perPage)
            ->through(static function ($model) {
                return $model->getRelation('profile');
            });

        return ProfileLiteResource::collection($paginator);
    }

    public function suggestions(Request $request, User $user): AnonymousResourceCollection
    {
        $limit = (int) $request->integer('limit', 10);

        $list = $this->service->suggestions($user, $limit);

        $profiles = $list->map(fn ($u) => $u->profile);

        return ProfileLiteResource::collection($profiles);
    }

    public function mutuals(Request $request, User $user): AnonymousResourceCollection
    {
        $me = $request->user();
        $perPage = (int) $request->integer('per_page', 15);

        $paginator = $this->service->mutuals($me, $user, $perPage);

        return ProfileLiteResource::collection($paginator);
    }
}
