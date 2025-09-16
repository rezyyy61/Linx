<?php

namespace App\Http\Controllers\Api\Comment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\ListChildCommentsRequest;
use App\Http\Requests\Comment\ListRootCommentsRequest;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Http\Requests\Comment\UpdateCommentRequest;
use App\Http\Resources\Comment\CommentCursorCollection;
use App\Http\Resources\Comment\CommentResource;
use App\Services\Comment\Contracts\CommentService as Service;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CommentController extends Controller
{
    public function index(ListRootCommentsRequest $request, Service $service): JsonResponse
    {
        $paginator = $service->listRoots(
            $request->string('commentable_type')->toString(),
            $request->input('commentable_id'),
            $request->input('cursor'),
            $request->integer('per_page', 20),
            $request->input('sort', 'new'),
            false
        );

        return (new CommentCursorCollection($paginator))->response();
    }

    public function children(int $parentId, ListChildCommentsRequest $request, Service $service): JsonResponse
    {
        $paginator = $service->listChildren(
            $parentId,
            $request->input('cursor'),
            $request->integer('per_page', 20),
            $request->input('sort', 'new'),
            false
        );

        return (new CommentCursorCollection($paginator))->response();
    }

    public function store(StoreCommentRequest $request, Service $service): JsonResponse
    {
        $dto = \App\Services\Comment\DTOs\CreateCommentData::make(
            $request->string('commentable_type')->toString(),
            $request->input('commentable_id'),
            $request->input('parent_id'),
            $request->string('body')->toString()
        );

        $comment = $service->create($dto, $request->user());

        return (new CommentResource($comment))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(int $id, UpdateCommentRequest $request, Service $service): JsonResponse
    {
        $dto = \App\Services\Comment\DTOs\UpdateCommentData::make(
            $id,
            $request->string('body')->toString()
        );

        $comment = $service->update($dto, $request->user());

        return (new CommentResource($comment))->response();
    }

    public function destroy(int $id, Service $service): JsonResponse
    {
        $service->delete($id, request()->user());

        return response()->json(['deleted' => true]);
    }
}
