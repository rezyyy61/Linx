<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\Link\ProfileLinkReorderRequest;
use App\Http\Requests\Profile\Link\ProfileLinkStoreRequest;
use App\Http\Requests\Profile\Link\ProfileLinkUpdateRequest;
use App\Services\Profile\ProfileLinkService;
use Illuminate\Http\Request;

class LinkController extends Controller
{
    public function index(Request $request, ProfileLinkService $service)
    {
        $links = $service->list($request->user());

        return response()->json(['ok' => true, 'data' => ['links' => $links]]);
    }

    public function store(ProfileLinkStoreRequest $request, ProfileLinkService $service)
    {
        $link = $service->create($request->user(), $request->validated());

        return response()->json(['ok' => true, 'data' => ['link' => $link]], 201);
    }

    public function update(ProfileLinkUpdateRequest $request, ProfileLinkService $service, int $id)
    {
        $link = $service->update($request->user(), $id, $request->validated());

        return response()->json(['ok' => true, 'data' => ['link' => $link]]);
    }

    public function destroy(Request $request, ProfileLinkService $service, int $id)
    {
        $service->delete($request->user(), $id);

        return response()->json(['ok' => true]);
    }

    public function reorder(ProfileLinkReorderRequest $request, ProfileLinkService $service)
    {
        $links = $service->reorder($request->user(), $request->validated('ids'));

        return response()->json(['ok' => true, 'data' => ['links' => $links]]);
    }
}
