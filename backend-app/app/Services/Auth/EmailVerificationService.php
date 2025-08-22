<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

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

    public function resendPublic(string $email): JsonResponse
    {
        $user = User::where('email', $email)->first();
        if ($user && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return response()->json(['ok' => true, 'message' => 'verification_link_sent'], 200);
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        if (! $request->user()->hasVerifiedEmail()) {
            if ($request->user()->markEmailAsVerified()) {
                event(new Verified($request->user()));
            }
        }

        return $this->redirectFront('verified=1');
    }

    public function verifySigned(Request $request, int $id, string $hash): RedirectResponse
    {
        if (! URL::hasValidSignature($request)) {
            return $this->redirectFront('verified=invalid');
        }

        $user = User::findOrFail($id);

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return $this->redirectFront('verified=invalid');
        }

        if (! $user->hasVerifiedEmail()) {
            if ($user->markEmailAsVerified()) {
                event(new Verified($user));
            }
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return $this->redirectFront('verified=1');
    }

    protected function redirectFront(string $qs = ''): RedirectResponse
    {
        $front = rtrim(config('app.frontend_url', '/'), '/');
        $url = $front ?: '/';
        $sep = str_contains($url, '?') ? '&' : '?';

        return redirect()->to($url.($qs ? $sep.$qs : ''));
    }
}
