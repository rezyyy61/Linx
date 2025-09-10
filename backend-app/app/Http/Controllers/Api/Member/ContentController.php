<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\ContentRequest;
use App\Http\Resources\Member\ContentResource;
use App\Models\Member\MemberContent;
use App\Models\User;
use App\Services\Member\ContentService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ContentController extends Controller
{
    public function __construct(protected ContentService $service)
    {
        $this->authorizeResource(MemberContent::class, 'content');
    }

    public function index(Request $request, User $user): AnonymousResourceCollection
    {
        $type = $request->get('type');
        $items = $this->service->listForOwner($user, $type);

        return ContentResource::collection($items);
    }

    public function store(ContentRequest $request, User $user): ContentResource
    {
        $content = $this->service->create($user, $request->validated());

        return new ContentResource($content);
    }

    public function update(ContentRequest $request, MemberContent $content): ContentResource
    {
        $this->authorize('update', $content);
        $content->update($request->validated());

        return new ContentResource($content);
    }

    public function destroy(MemberContent $content)
    {
        $this->authorize('delete', $content);
        $content->delete();

        return response()->noContent();
    }

    public function schedule(Request $request, MemberContent $content): ContentResource
    {
        $this->authorize('update', $content);
        $when = $request->date('scheduled_at');
        $content = $this->service->schedule($content, $when);

        return new ContentResource($content);
    }
}
