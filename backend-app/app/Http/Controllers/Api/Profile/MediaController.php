<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\Media\ProfileFileAttachRequest;
use App\Http\Requests\Profile\Media\ProfileLogoAttachRequest;
use App\Services\Profile\ProfileMediaService;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function setLogo(ProfileLogoAttachRequest $request, ProfileMediaService $service)
    {
        $profile = $service->setLogo($request->user(), (int) $request->validated('media_id'));

        return response()->json(['ok' => true, 'data' => ['profile' => $profile]]);
    }

    public function clearLogo(Request $request, ProfileMediaService $service)
    {
        $profile = $service->clearLogo($request->user());

        return response()->json(['ok' => true, 'data' => ['profile' => $profile]]);
    }

    public function files(Request $request, ProfileMediaService $service)
    {
        $files = $service->listFiles($request->user());

        return response()->json(['ok' => true, 'data' => ['files' => $files]]);
    }

    public function addFile(ProfileFileAttachRequest $request, ProfileMediaService $service)
    {
        $profile = $service->addFile(
            $request->user(),
            (int) $request->validated('media_id'),
            $request->validated('order')
        );

        return response()->json(['ok' => true, 'data' => ['profile' => $profile]], 201);
    }

    public function removeFile(Request $request, ProfileMediaService $service, int $mediaId)
    {
        $profile = $service->removeFile($request->user(), $mediaId);

        return response()->json(['ok' => true, 'data' => ['profile' => $profile]]);
    }
}
