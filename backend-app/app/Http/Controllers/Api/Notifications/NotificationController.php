<?php

namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $service) {}

    public function index(Request $request)
    {
        $perPage = (int) ($request->integer('per_page') ?: 15);

        return response()->json($this->service->paginateFor($request->user(), $perPage));
    }

    public function read(Request $request, int $id)
    {
        $this->service->markAsRead($request->user(), $id);

        return response()->json(['status' => 'ok']);
    }

    public function readAll(Request $request)
    {
        $count = $this->service->markAllAsRead($request->user());

        return response()->json(['updated' => $count]);
    }

    public function destroy(Request $request, int $id)
    {
        $this->service->delete($request->user(), $id);

        return response()->json(['status' => 'ok']);
    }
}
