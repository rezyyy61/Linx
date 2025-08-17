<?php

use App\Http\Controllers\Api\media\MediaAttachController;
use App\Http\Controllers\Api\media\MediaController;
use Illuminate\Support\Facades\Route;

Route::prefix('media')->group(function () {
    Route::post('presigned', [MediaController::class, 'createPresignedUrl']);
    Route::post('{media}/finalize', [MediaController::class, 'finalizeUpload']);
    Route::post('/{media}/attach', [MediaAttachController::class, 'attach']);
    Route::post('/{media}/detach', [MediaAttachController::class, 'detach']);
});
