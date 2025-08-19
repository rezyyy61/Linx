<?php

namespace App\Services\Auth;

use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\Auth\Authenticatable;

class EmailVerificationService
{
    public function resend(Authenticatable $user): JsonResponse
    {
        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'already_verified']);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'verification_link_sent'], 202);
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        if (! $request->user()->hasVerifiedEmail()) {
            if ($request->user()->markEmailAsVerified()) {
                event(new Verified($request->user()));
            }
        }

        return redirect()->to(config('app.frontend_url', '/?verified=1'));
    }
}
