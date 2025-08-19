<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class RegisterService
{
    public function register(Request $request, array $data): array
    {
        $this->ensureRegisterNotRateLimited($data['email'], (string) $request->ip());

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        event(new Registered($user));
        $user->sendEmailVerificationNotification();

        RateLimiter::clear($this->registerKey($data['email'], (string) $request->ip()));

        return [
            'http' => 201,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
            ],
        ];
    }

    private function ensureRegisterNotRateLimited(string $email, string $ip): void
    {
        if (RateLimiter::tooManyAttempts($this->registerKey($email, $ip), 5)) {
            abort(429, 'Too many register attempts. Try again later.');
        }
        RateLimiter::hit($this->registerKey($email, $ip), 60);
    }

    private function registerKey(string $email, string $ip): string
    {
        return 'register|'.Str::lower($email).'|'.$ip;
    }
}
