<?php

namespace App\Http\Controllers\Api\media;

use App\Enums\MediaType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Media\CreatePresignedUrlRequest;
use App\Http\Requests\Media\FinalizeUploadRequest;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use App\Services\Media\MediaService;
use Illuminate\Http\JsonResponse;

class MediaController extends Controller
{
    public function __construct(private MediaService $mediaService) {}

    public function createPresignedUrl(CreatePresignedUrlRequest $request): JsonResponse
    {
        $typeEnum = MediaType::from($request->input('type'));

        $data = $this->mediaService->createPresignedUploadUrl(
            $request->input('extension'),
            $typeEnum
        );

        return response()->json($data);
    }

    public function finalizeUpload(FinalizeUploadRequest $request, Media $media): MediaResource
    {
        $media = $this->mediaService->finalizeUpload($media, $request->validated());

        return new MediaResource($media);
    }
}
