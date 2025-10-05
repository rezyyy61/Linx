<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Public\Publication;

use App\Http\Controllers\Controller;
use App\Http\Resources\Publication\PublicationResource;
use App\Services\Publication\PublicPublicationService;
use Illuminate\Http\Request;

class PublicPublicationController extends Controller
{
    public function __construct(private PublicPublicationService $service) {}

    public function index(Request $request)
    {
        $rows = $this->service->list([
            'q' => $request->string('q')->toString(),
            'language' => $request->input('language'),
            'date_range' => $request->input('date_range', 'all'),
            'published_from' => $request->input('published_from'),
            'published_to' => $request->input('published_to'),
            'order_by' => $request->input('order_by'),
            'order_dir' => $request->input('order_dir'),
            'per_page' => $request->integer('per_page', 15),
        ]);

        return PublicationResource::collection($rows);
    }

    public function show(string $slug)
    {
        $p = $this->service->findBySlug($slug);
        if (! $p) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return new PublicationResource($p);
    }
}
