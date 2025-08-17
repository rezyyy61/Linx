<?php

namespace App\Http\Controllers\Api\media;

use App\Contracts\Mediable;
use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\Media\MediaService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class MediaAttachController extends Controller
{
    public function __construct(private MediaService $mediaService) {}

    public function attach(Request $request, Media $media)
    {
        $validated = $request->validate([
            'model_type' => 'required|string', // eg: App\\Models\\Post
            'model_id'   => 'required|integer',
            'collection' => 'nullable|string',
            'order'      => 'nullable|integer',
        ]);

        /** @var class-string<Model> $class */
        $class = $validated['model_type'];

        /** @var Model $model */
        $model = $class::query()->findOrFail($validated['model_id']);

        if (! $model instanceof Mediable) {
            abort(400, 'Model is not mediable');
        }
        /** @var Mediable $model */

        $this->mediaService->attachMedia(
            $model,
            $media,
            $validated['collection'] ?? 'default',
            $validated['order'] ?? 0
        );

        return response()->json(['ok' => true]);
    }

    public function detach(Request $request, Media $media)
    {
        $validated = $request->validate([
            'model_type' => 'required|string',
            'model_id'   => 'required|integer',
            'collection' => 'nullable|string',
        ]);

        /** @var class-string<Model> $class */
        $class = $validated['model_type'];

        /** @var Model $model */
        $model = $class::query()->findOrFail($validated['model_id']);

        if (! $model instanceof Mediable) {
            abort(400, 'Model is not mediable');
        }
        /** @var Mediable $model */

        $this->mediaService->detachMedia(
            $model,
            $media,
            $validated['collection'] ?? null
        );

        return response()->json(['ok' => true]);
    }
}
