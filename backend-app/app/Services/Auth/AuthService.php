<?php

namespace App\Services\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthService
{
    public function login(Request $request, string $email, string $password, bool $remember): array
    {
        $this->ensureLoginNotRateLimited($email, (string) $request->ip());
        if (! Auth::attempt(['email' => $email, 'password' => $password], $remember)) {
            RateLimiter::hit($this->loginKey($email, (string) $request->ip()), 60);

            return ['http' => 422, 'error' => 'Invalid credentials'];
        }

        RateLimiter::clear($this->loginKey($email, (string) $request->ip()));
        $request->session()->regenerate();
        $user = $request->user();

        if (! $user->hasVerifiedEmail()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $user->sendEmailVerificationNotification();

            return ['http' => 403, 'error' => 'Email not verified.', 'code' => 'email_unverified'];
        }

        return ['http' => 200, 'user' => $user];
    }

    public function logout(Request $request): void
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    public function me(Request $request): ?Authenticatable
    {
        return $request->user();
    }

    private function ensureLoginNotRateLimited(string $email, string $ip): void
    {
        if (RateLimiter::tooManyAttempts($this->loginKey($email, $ip), 5)) {
            abort(429, 'Too many login attempts. Try again later.');
        }
    }

    private function loginKey(string $email, string $ip): string
    {
        return Str::lower($email).'|'.$ip;
    }
}
