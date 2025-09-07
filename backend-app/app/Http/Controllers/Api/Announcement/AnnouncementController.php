<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Announcement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Announcement\ListAnnouncementRequest;
use App\Http\Requests\Announcement\StoreAnnouncementRequest;
use App\Http\Requests\Announcement\UpdateAnnouncementRequest;
use App\Http\Resources\Announcement\AnnouncementResource;
use App\Models\Announcement\Announcement;
use App\Services\Announcement\AnnouncementService;

class AnnouncementController extends Controller
{
    public function __construct(private AnnouncementService $service)
    {
        $this->authorizeResource(Announcement::class, 'announcement');
    }

    public function index(ListAnnouncementRequest $request)
    {
        $paginated = $this->service->list($request->validated());

        return response()->json([
            'data' => AnnouncementResource::collection($paginated->getCollection()),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ],
        ]);
    }

    public function store(StoreAnnouncementRequest $request)
    {
        $id = $this->service->create($request->validated());
        $announcement = Announcement::with(['covers', 'documents', 'owner'])->findOrFail($id);

        return (new AnnouncementResource($announcement))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Announcement $announcement)
    {
        $announcement->loadMissing(['covers', 'documents', 'owner']);

        return new AnnouncementResource($announcement);
    }

    public function update(UpdateAnnouncementRequest $request, Announcement $announcement)
    {
        $announcement = $this->service->update($announcement, $request->validated());
        $announcement->loadMissing(['covers', 'documents', 'owner']);

        return new AnnouncementResource($announcement);
    }

    public function destroy(Announcement $announcement)
    {
        $this->service->delete($announcement);

        return response()->json([], 204);
    }
}
