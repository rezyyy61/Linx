<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Publication;

use App\Http\Controllers\Controller;
use App\Http\Requests\Publication\StorePublicationRequest;
use App\Http\Requests\Publication\UpdatePublicationRequest;
use App\Http\Resources\Publication\PublicationResource;
use App\Models\Publication\Publication;
use App\Services\Publication\PublicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class PublicationController extends Controller
{
    public function __construct(private PublicationService $service) {}

    public function index(): AnonymousResourceCollection
    {
        $filters = request()->all();
        $items = $this->service->list($filters);

        return PublicationResource::collection($items);
    }

    public function store(StorePublicationRequest $request): JsonResponse
    {
        $id = $this->service->create($request->validated());
        $model = Publication::with(['owner', 'covers', 'documents'])->findOrFail($id);

        return (new PublicationResource($model))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Publication $publication): PublicationResource
    {
        $publication->load(['owner', 'covers', 'documents']);

        return new PublicationResource($publication);
    }

    public function update(UpdatePublicationRequest $request, Publication $publication): PublicationResource
    {
        $model = $this->service->update($publication, $request->validated());
        $model->load(['owner', 'covers', 'documents']);

        return new PublicationResource($model);
    }

    public function destroy(Publication $publication): JsonResponse
    {
        $this->service->delete($publication);

        return response()->json([], Response::HTTP_NO_CONTENT);
    }
}
