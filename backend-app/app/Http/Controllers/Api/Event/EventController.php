<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Event;

use App\Http\Controllers\Controller;
use App\Http\Requests\Event\ListEventRequest;
use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Http\Resources\Event\EventResource;
use App\Models\Event\Event;
use App\Services\Event\EventService;

class EventController extends Controller
{
    public function __construct(private EventService $service)
    {
        $this->authorizeResource(Event::class, 'event');
    }

    /**
     * GET /api/events
     */
    public function index(ListEventRequest $request)
    {
        $paginated = $this->service->list($request->validated());

        return response()->json([
            'data' => EventResource::collection($paginated->getCollection()),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ],
        ]);
    }

    /**
     * POST /api/events
     */
    public function store(StoreEventRequest $request)
    {
        $id = $this->service->create($request->validated());
        $event = Event::with(['covers', 'documents'])->findOrFail($id);

        return (new EventResource($event))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/events/{event}
     */
    public function show(Event $event)
    {
        $event->loadMissing(['covers', 'documents', 'settings']);

        return new EventResource($event);
    }

    /**
     * PUT/PATCH /api/events/{event}
     */
    public function update(UpdateEventRequest $request, Event $event)
    {
        $event = $this->service->update($event, $request->validated());
        $event->loadMissing(['covers', 'documents', 'settings']);

        return new EventResource($event);
    }

    /**
     * DELETE /api/events/{event}
     */
    public function destroy(Event $event)
    {
        $this->service->delete($event);

        return response()->json([], 204);
    }
}
