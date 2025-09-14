<?php

declare(strict_types=1);

namespace App\Services\Public\Post;

use App\Models\Post\Comment;
use App\Models\Post\Post;
use Illuminate\Http\Request;

class PublicCommentQuery
{
    public function index(Request $request, Post $post)
    {
        $perPage = (int) ($request->integer('per_page') ?: 20);
        $query = Comment::query()
            ->where('post_id', $post->id)
            ->with(['user.profile.translations', 'user.profile.media'])
            ->orderByDesc('created_at');

        return $query->cursorPaginate($perPage);
    }

    public function findOrFail(Post $post, string $id): Comment
    {
        return Comment::query()
            ->where('post_id', $post->id)
            ->findOrFail($id);
    }
}
