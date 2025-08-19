<?php

use App\Http\Controllers\Api\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Api\Auth\RegisteredUserController;
use App\Http\Controllers\Api\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

// Route::prefix('api')->group(function () {
//    Route::prefix('auth')->group(function () {
//        Route::post('register', [RegisteredUserController::class, 'store'])->middleware('guest');
//        Route::post('login', [AuthenticatedSessionController::class, 'store'])->middleware('guest');
//        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth');
//        Route::get('me', [AuthenticatedSessionController::class, 'me'])->middleware('auth');
//
//        Route::post('email/verification-notification', EmailVerificationNotificationController::class)
//            ->middleware(['auth', 'throttle:6,1']);
//
//        Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
//            ->middleware(['signed', 'throttle:6,1'])
//            ->name('verification.verify');
//    });
// });
