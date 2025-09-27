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

    public function storeGeneric(CreateRepostRequest $request): JsonResponse
    {
        $alias = trim((string) $request->input('shareable_alias', ''));
        $type = trim((string) $request->input('shareable_type', ''));
        if ($alias !== '') {
            $mapped = config('share.aliases.'.$alias);
            if (is_string($mapped) && class_exists($mapped)) {
                $type = $mapped;
            }
        }

        if ($type === '' || ! class_exists($type)) {
            abort(422, 'Invalid shareable_type');
        }

        if (! is_subclass_of($type, \Illuminate\Database\Eloquent\Model::class) || ! is_subclass_of($type, \App\Models\Share\Contracts\Shareable::class)) {
            abort(422, 'Type must be an Eloquent Model and Shareable');
        }

        $id = (int) $request->input('shareable_id');
        if ($id < 1) {
            abort(422, 'Invalid shareable_id');
        }

        /** @var (\App\Models\Share\Contracts\Shareable&\Illuminate\Database\Eloquent\Model)|null $model */
        $model = $type::query()->find($id);
        if (! $model) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException;
        }

        $actor = $request->user();
        $created = $this->service->createForShareable(
            $actor,
            $model,
            $request->string('text')->toString() ?: null,
            $request->input('visibility') ?: 'public'
        );

        return response()->json([
            'id' => $created->getKey(),
            'url' => url('/posts/'.$created->getKey()),
        ], 201);
    }
}
