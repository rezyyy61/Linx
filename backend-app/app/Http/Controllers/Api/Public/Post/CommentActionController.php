<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Public\Post;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicApi\PublicMiniUserResource;
use App\Models\Post\Comment;
use App\Models\Post\CommentLike;
use App\Models\Post\Post;
use App\Services\Public\Post\CommentLikeService;
use Illuminate\Http\Request;

class CommentActionController extends Controller
{
    public function __construct(private CommentLikeService $likes) {}

    public function toggleLike(Request $request, Post $post, Comment $comment)
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }
        if ((string) $comment->post_id !== (string) $post->id) {
            abort(404);
        }

        $res = $this->likes->toggle($comment, $user);

        return response()->json($res);
    }

    public function index(Request $request, Post $post, Comment $comment)
    {
        if ((string) $comment->post_id !== (string) $post->id) {
            abort(404);
        }

        $perPage = (int) $request->integer('per_page', 20);
        $cursor = $request->string('cursor')->toString() ?: null;

        $likes = CommentLike::query()
            ->where('comment_id', $comment->id)
            ->with(['user.profile.translations', 'user.profile.logo', 'user.profile.media'])
            ->orderByDesc('id')
            ->cursorPaginate($perPage, ['*'], 'cursor', $cursor);

        $users = $likes->getCollection()->map(fn ($like) => $like->user);

        return PublicMiniUserResource::collection($users)->additional([
            'links' => [
                'next' => $likes->nextPageUrl(),
                'prev' => $likes->previousPageUrl(),
            ],
            'meta' => [
                'next_cursor' => optional($likes->nextCursor())->encode(),
                'prev_cursor' => optional($likes->previousCursor())->encode(),
                'total' => null,
            ],
        ]);
    }
}
