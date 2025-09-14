<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Public\Post;

use App\Http\Controllers\Controller;
use App\Models\Post\Post;
use App\Services\Public\Post\PostLikeService;
use App\Services\Public\Post\PostSaveService;
use Illuminate\Http\Request;

class PostActionController extends Controller
{
    public function __construct(private PostLikeService $likes, private PostSaveService $saves) {}

    public function toggleLike(Request $request, Post $post)
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $res = $this->likes->toggle($post, $user);

        return response()->json($res);
    }

    public function likesPreview(Request $request, Post $post)
    {
        $data = $this->likes->preview($post, 2);
        $users = \App\Http\Resources\PublicApi\PublicMiniUserResource::collection($data['users'])->resolve();

        return response()->json([
            'total' => $data['total'],
            'users' => $users,
        ]);
    }

    public function likes(Request $request, Post $post)
    {
        $limit = min((int) $request->query('limit', 30), 100);
        $cursor = $request->query('cursor');

        $paginated = $this->likes->list($post, $limit, $cursor);
        $users = collect($paginated->items())->map(fn ($like) => $like->user);
        $data = \App\Http\Resources\PublicApi\PublicMiniUserResource::collection($users)->resolve();

        return response()->json([
            'data' => $data,
            'next_cursor' => $paginated->nextCursor()?->encode(),
            'prev_cursor' => $paginated->previousCursor()?->encode(),
        ]);
    }

    public function toggleSave(Request $request, Post $post)
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }
        $res = $this->saves->toggle($post, $user);

        return response()->json($res);
    }
}
