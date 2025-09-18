<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Post;

use App\Http\Controllers\Controller;
use App\Http\Requests\Post\CreateRepostRequest;
use App\Models\Post\Post;
use App\Services\Post\RepostService;
use Illuminate\Http\JsonResponse;

class RepostController extends Controller
{
    public function __construct(protected RepostService $service) {}

    public function store(CreateRepostRequest $request, Post $post): JsonResponse
    {
        $actor = $request->user();
        $created = $this->service->create(
            $actor,
            $post,
            $request->string('text')->toString() ?: null,
            $request->input('visibility') ?: 'public'
        );

        return response()->json([
            'id' => $created->getKey(),
            'url' => url('/posts/'.$created->getKey()),
        ], 201);
    }
}
