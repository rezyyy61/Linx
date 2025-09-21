<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Public\Event;

use App\Http\Controllers\Controller;
use App\Http\Resources\Event\EventResource;
use App\Services\Event\PublicEventService;
use Illuminate\Http\Request;

class PublicEventController extends Controller
{
    public function __construct(private PublicEventService $service) {}

    public function index(Request $request)
    {
        $events = $this->service->list([
            'q' => $request->string('q')->toString(),
            'starts_from' => $request->input('starts_from'),
            'starts_to' => $request->input('starts_to'),
            'order_by' => $request->input('order_by'),
            'order_dir' => $request->input('order_dir'),
            'per_page' => $request->integer('per_page', 15),
        ]);

        return EventResource::collection($events);
    }

    public function show(string $slug)
    {
        $event = $this->service->findBySlug($slug);
        if (! $event) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return new EventResource($event);
    }
}
