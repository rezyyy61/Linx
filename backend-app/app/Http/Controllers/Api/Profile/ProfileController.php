<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\ProfileUpdateRequest;
use App\Http\Resources\Profile\ProfileResource;
use App\Services\Profile\ProfileUpdateService;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function me(Request $request)
    {
        $user = $request->user();

        $profile = $user->profile()
            ->with([
                'translations',
                'links',
                'values',
                'media' => fn ($q) => $q->withPivot('collection', 'order_column'),
            ])
            ->firstOrFail();

        return response()->json([
            'ok' => true,
            'data' => [
                'profile' => new ProfileResource($profile),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ],
        ]);
    }

    public function updateMe(ProfileUpdateRequest $request, ProfileUpdateService $service)
    {
        $profile = $service->update($request->user(), $request->validated());

        $profile->load([
            'translations',
            'links',
            'values',
            'media' => fn ($q) => $q->withPivot('collection', 'order_column'),
        ]);

        return response()->json([
            'ok' => true,
            'data' => [
                'profile' => new ProfileResource($profile),
            ],
        ]);
    }
}
