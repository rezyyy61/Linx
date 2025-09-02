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

        $perPage = min((int) $request->integer('per_page', 20), 100);
        $sortDir = $request->get('sort') === 'oldest' ? 'asc' : 'desc';

        $q = PostModel::query()
            ->with('media');

        if ($request->filled('user_id')) {
            $q->where('user_id', (int) $request->get('user_id'));
        }

        if ($search = trim((string) $request->get('q', ''))) {
            $q->where(function ($x) use ($search) {
                $x->where('content', 'like', "%{$search}%");
            });
        }

        if ($vis = $request->get('visibility')) {
            if (in_array($vis, ['public', 'private', 'friends'], true)) {
                $q->where('visibility', $vis);
            }
        }

        if (($st = $request->get('status')) && $st !== 'all') {
            if (in_array($st, ['draft', 'published'], true)) {
                $q->where('status', $st);
            }
        }

        $from = $request->get('date_from');
        $to = $request->get('date_to');
        if ($from && $to && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        if ($from) {
            $q->whereRaw('DATE(COALESCE(published_at, created_at)) >= ?', [$from]);
        }
        if ($to) {
            $q->whereRaw('DATE(COALESCE(published_at, created_at)) <= ?', [$to]);
        }

        $q->orderByRaw('COALESCE(published_at, created_at) '.$sortDir)
            ->orderBy('id', $sortDir);

        $posts = $q->cursorPaginate($perPage)->withQueryString();

        return PostResource::collection($posts)->additional([
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
