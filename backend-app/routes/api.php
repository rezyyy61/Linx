<?php

use App\Http\Controllers\Api\media\MediaAttachController;
use App\Http\Controllers\Api\media\MediaController;
use App\Http\Controllers\Api\Profile\LinkController;
use App\Http\Controllers\Api\Profile\ProfileController;
use App\Http\Controllers\Api\Profile\ValueController;
use Illuminate\Support\Facades\Route;

Route::prefix('media')->group(function () {
    Route::post('presigned', [MediaController::class, 'createPresignedUrl']);
    Route::post('{media}/finalize', [MediaController::class, 'finalizeUpload']);
    Route::post('/{media}/attach', [MediaAttachController::class, 'attach']);
    Route::post('/{media}/detach', [MediaAttachController::class, 'detach']);
    Route::post('/{media}/attach-single', [MediaAttachController::class, 'attachSingle']);
});

Route::middleware('auth:sanctum')
    ->prefix('profile/me')
    ->as('profile.me.')
    ->group(function () {

        // Me
        Route::get('/', [ProfileController::class, 'me'])->name('show');
        Route::patch('/', [ProfileController::class, 'updateMe'])->name('update');
        Route::put('/', [ProfileController::class, 'updateMe'])->name('replace');

        // Media (Logo + Files)
        Route::prefix('')->as('media.')->group(function () {
            Route::post('/logo', [MediaController::class, 'setLogo'])->name('logo.set');
            Route::delete('/logo', [MediaController::class, 'clearLogo'])->name('logo.clear');

            Route::prefix('files')->as('files.')->group(function () {
                Route::get('/', [MediaController::class, 'files'])->name('index');
                Route::post('/', [MediaController::class, 'addFile'])->name('store');
                Route::delete('/{mediaId}', [MediaController::class, 'removeFile'])
                    ->whereUuid('mediaId')
                    ->name('destroy');
            });
        });

        // Links
        Route::prefix('links')->as('links.')->group(function () {
            Route::get('/', [LinkController::class, 'index'])->name('index');
            Route::post('/', [LinkController::class, 'store'])->name('store');
            Route::patch('/{id}', [LinkController::class, 'update'])->whereNumber('id')->name('update');
            Route::put('/{id}', [LinkController::class, 'update'])->whereNumber('id')->name('replace');
            Route::delete('/{id}', [LinkController::class, 'destroy'])->whereNumber('id')->name('destroy');
            Route::put('/reorder', [LinkController::class, 'reorder'])->name('reorder');
        });

        // Values
        Route::prefix('values')->as('values.')->group(function () {
            Route::get('/', [ValueController::class, 'index'])->name('index');
            Route::post('/', [ValueController::class, 'store'])->name('store');
            Route::patch('/{id}', [ValueController::class, 'update'])->whereNumber('id')->name('update');
            Route::put('/{id}', [ValueController::class, 'update'])->whereNumber('id')->name('replace');
            Route::delete('/{id}', [ValueController::class, 'destroy'])->whereNumber('id')->name('destroy');
            Route::put('/reorder', [ValueController::class, 'reorder'])->name('reorder');
        });
    });
