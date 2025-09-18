<?php

use App\Http\Controllers\Api\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Api\Auth\GuestVerifyEmailController;
use App\Http\Controllers\Api\Auth\PasswordResetController;
use App\Http\Controllers\Api\Auth\PublicVerificationController;
use App\Http\Controllers\Api\Auth\RegisteredUserController;
use App\Http\Controllers\Api\Share\ShareResolveController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::prefix('api')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('register', [RegisteredUserController::class, 'store'])->middleware('guest');
        Route::post('login', [AuthenticatedSessionController::class, 'store'])->middleware('guest');
        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth');
        Route::get('me', [AuthenticatedSessionController::class, 'me'])->middleware(['auth', 'verified']);

        Route::post('password/forgot', [PasswordResetController::class, 'forgot'])->middleware('throttle:5,1');
        Route::post('password/reset', [PasswordResetController::class, 'reset'])->middleware('throttle:10,1');

        Route::post('email/verification-notification', EmailVerificationNotificationController::class)
            ->middleware(['auth', 'throttle:6,1']);

        Route::post('resend-verification', [PublicVerificationController::class, 'resend'])
            ->middleware(['throttle:3,1']);

        Route::get('verify-email/{id}/{hash}', GuestVerifyEmailController::class)
            ->middleware(['signed', 'throttle:6,1'])
            ->name('verification.verify');
    });
});

Route::get('/'.trim(config('share.route_prefix'), '/').'/{code}', ShareResolveController::class)
    ->middleware('throttle:resolve-share')
    ->name('share.resolve');
