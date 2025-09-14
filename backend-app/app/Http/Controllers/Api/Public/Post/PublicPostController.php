<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Public\Post;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicApi\PublicPostResource;
use App\Services\Public\Post\PublicPostQuery;
use Illuminate\Http\Request;

class PublicPostController extends Controller
{
    public function __construct(private PublicPostQuery $query) {}

    public function index(Request $request)
    {
        $posts = $this->query->index($request);

        return PublicPostResource::collection($posts)->additional([
            'links' => [
                'next' => $posts->nextPageUrl(),
                'prev' => $posts->previousPageUrl(),
            ],
            'meta' => [
                'next_cursor' => optional($posts->nextCursor())->encode(),
                'prev_cursor' => optional($posts->previousCursor())->encode(),
            ],
        ]);
    }

    public function show(int $id)
    {
        $post = $this->query->findOrFail($id);

        return new PublicPostResource($post);
    }
}
