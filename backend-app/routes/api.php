<?php

use App\Http\Controllers\Api\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Api\Auth\PasswordResetController;
use App\Http\Controllers\Api\Auth\RegisteredUserController;
use App\Http\Controllers\Api\Auth\VerifyEmailController;
use App\Http\Controllers\Api\media\MediaAttachController;
use App\Http\Controllers\Api\media\MediaController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:5,1');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::post('/password/forgot', [PasswordResetController::class, 'forgot'])->middleware('throttle:5,1');
    Route::post('/password/reset', [PasswordResetController::class, 'reset'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::get('/me', [AuthenticatedSessionController::class, 'me']);
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);
    Route::post('/email/verification-notification', EmailVerificationNotificationController::class)->middleware('throttle:6,1');
});

Route::get('/email/verify/{id}/{hash}', VerifyEmailController::class)->middleware(['signed', 'auth'])->name('verification.verify');

Route::prefix('media')->group(function () {
    Route::post('presigned', [MediaController::class, 'createPresignedUrl']);
    Route::post('{media}/finalize', [MediaController::class, 'finalizeUpload']);
    Route::post('/{media}/attach', [MediaAttachController::class, 'attach']);
    Route::post('/{media}/detach', [MediaAttachController::class, 'detach']);
});
