<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Post;

use App\Http\Controllers\Controller;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Http\Resources\Post\PostResource;
use App\Models\Post\Post as PostModel;
use App\Services\Post\PostService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PostController extends Controller
{
    public function __construct(private PostService $service) {}

    public function index(Request $request)
    {
        Gate::authorize('viewAny', PostModel::class);
        $query = PostModel::query()->with('media')->latest('published_at');
        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->get('user_id'));
        }
        $posts = $query->cursorPaginate(20);

        return PostResource::collection($posts);
    }

    public function show(PostModel $post)
    {
        Gate::authorize('view', $post);
        $post->load('media');

        return new PostResource($post);
    }

    public function store(StorePostRequest $request)
    {
        Gate::authorize('create', PostModel::class);
        $post = $this->service->create($request->validated(), $request->user());

        return (new PostResource($post))->response()->setStatusCode(201);
    }

    public function update(UpdatePostRequest $request, PostModel $post)
    {
        Gate::authorize('update', $post);
        $post = $this->service->update($post, $request->validated());

        return new PostResource($post);
    }

    public function destroy(PostModel $post)
    {
        Gate::authorize('delete', $post);
        $this->service->delete($post);

        return response()->noContent();
    }
}
