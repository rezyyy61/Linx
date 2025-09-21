<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Event;

use App\Http\Controllers\Controller;
use App\Models\Event\Event;
use App\Services\Event\EventJoinService;
use Illuminate\Http\Request;

class EventJoinController extends Controller
{
    public function __construct(private EventJoinService $service) {}

    public function join(Request $request, Event $event)
    {
        $this->service->join($event);

        return response()->json(['joined' => true]);
    }

    public function unjoin(Request $request, Event $event)
    {
        $this->service->unjoin($event);

        return response()->json(['joined' => false]);
    }

    public function status(Request $request, Event $event)
    {
        return response()->json([
            'joined' => $this->service->hasJoined($event),
            'count' => $this->service->count($event),
        ]);
    }
}
