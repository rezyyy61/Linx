<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Public\Announcement;

use App\Http\Controllers\Controller;
use App\Http\Resources\Announcement\AnnouncementResource;
use App\Services\Announcement\PublicAnnouncementService;
use Illuminate\Http\Request;

class PublicAnnouncementController extends Controller
{
    public function __construct(private PublicAnnouncementService $service) {}

    public function index(Request $request)
    {
        $rows = $this->service->list([
            'q' => $request->string('q')->toString(),
            'published_from' => $request->input('published_from'),
            'published_to' => $request->input('published_to'),
            'only_pinned' => $request->boolean('only_pinned', false),
            'order_by' => $request->input('order_by'),
            'order_dir' => $request->input('order_dir'),
            'per_page' => $request->integer('per_page', 15),
        ]);

        return AnnouncementResource::collection($rows);
    }

    public function show(string $slug)
    {
        $a = $this->service->findBySlug($slug);
        if (! $a) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return new AnnouncementResource($a);
    }
}
