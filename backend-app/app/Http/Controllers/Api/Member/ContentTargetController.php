<?php

namespace App\Http\Controllers\Api\Member;

use App\Http\Controllers\Controller;
use App\Http\Resources\Member\ContentTargetResource;
use App\Models\Member\MemberContent;
use App\Models\Member\MemberContentTarget;
use App\Services\Member\TargetService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ContentTargetController extends Controller
{
    public function __construct(protected TargetService $service)
    {
        $this->authorizeResource(MemberContentTarget::class, 'target');
    }

    public function index(MemberContent $content): AnonymousResourceCollection
    {
        $items = $content->targets()->paginate(20);

        return ContentTargetResource::collection($items);
    }

    public function markSent(MemberContentTarget $target): ContentTargetResource
    {
        $this->authorize('update', $target);
        $target = $this->service->markSent($target);

        return new ContentTargetResource($target);
    }

    public function markFailed(MemberContentTarget $target): ContentTargetResource
    {
        $this->authorize('update', $target);
        $target = $this->service->markFailed($target, 'manual fail');

        return new ContentTargetResource($target);
    }

    public function markOpened(MemberContentTarget $target): ContentTargetResource
    {
        $this->authorize('update', $target);
        $target = $this->service->markOpened($target);

        return new ContentTargetResource($target);
    }

    public function markClicked(MemberContentTarget $target): ContentTargetResource
    {
        $this->authorize('update', $target);
        $target = $this->service->markClicked($target);

        return new ContentTargetResource($target);
    }
}
