<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Public\Post;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicApi\PublicCommentResource;
use App\Models\Post\Comment;
use App\Models\Post\Post;
use App\Services\Public\Post\CommentService;
use App\Services\Public\Post\PublicCommentQuery;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function __construct(
        private PublicCommentQuery $query,
        private CommentService $service
    ) {}

    public function index(Request $request, Post $post)
    {
        $comments = $this->query->index($request, $post);

        return PublicCommentResource::collection($comments)->additional([
            'links' => [
                'next' => $comments->nextPageUrl(),
                'prev' => $comments->previousPageUrl(),
            ],
            'meta' => [
                'next_cursor' => optional($comments->nextCursor())->encode(),
                'prev_cursor' => optional($comments->previousCursor())->encode(),
            ],
        ]);
    }

    public function store(Request $request, Post $post)
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'parent_id' => ['nullable', 'integer'],
        ]);

        $parentId = null;
        if (! empty($data['parent_id'])) {
            $parent = Comment::query()->where('id', (int) $data['parent_id'])->firstOrFail();
            if ((int) $parent->post_id !== (int) $post->id) {
                abort(422, 'Parent comment belongs to a different post.');
            }
            $parentId = (int) $parent->id;
        }

        $comment = $this->service->create($post, $user, $data['body'], $parentId);

        return new PublicCommentResource($comment->load(['user.profile.translations', 'user.profile.media']));
    }

    public function update(Request $request, Post $post, Comment $comment)
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }
        if ((string) $comment->post_id !== (string) $post->id) {
            abort(404);
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $updated = $this->service->update($comment, $user, $data['body']);

        return new PublicCommentResource($updated->load(['user.profile.translations', 'user.profile.media']));
    }

    public function destroy(Request $request, Post $post, Comment $comment)
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }
        if ((string) $comment->post_id !== (string) $post->id) {
            abort(404);
        }

        $this->service->delete($comment, $user);

        return response()->noContent();
    }
}
