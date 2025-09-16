<?php

namespace App\Http\Controllers\Api\Comment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\ListCommentLikersRequest;
use App\Http\Resources\Comment\CommentLikerCursorCollection;
use App\Services\Comment\Contracts\CommentService as Service;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CommentLikeController extends Controller
{
    public function index(int $commentId, ListCommentLikersRequest $request, Service $service): JsonResponse
    {
        $paginator = $service->likers(
            $commentId,
            $request->input('cursor'),
            $request->integer('per_page', 20)
        );

        return (new CommentLikerCursorCollection($paginator))->response();
    }

    public function toggle(int $commentId, Service $service): JsonResponse
    {
        $result = $service->toggleLike($commentId, request()->user());

        return response()->json([
            'liked' => $result['liked'],
            'count' => $result['count'],
        ], Response::HTTP_OK);
    }
}
