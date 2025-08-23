<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Profile\ProfileBootstrapService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class RegisterService
{
    public function register(Request $request, array $data): array
    {
        $this->ensureRegisterNotRateLimited($data['email'], (string) $request->ip());

        $result = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            $bootstrap = app(ProfileBootstrapService::class);
            $profile = $bootstrap->createForUser($user);

            return [$user, $profile];
        });

        [$user, $profile] = $result;

        Auth::login($user);
        $request->session()->regenerate();

        event(new Registered($user));

        RateLimiter::clear($this->registerKey($data['email'], (string) request()->ip()));

        return [
            'http' => 201,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
            ],
            'profile' => [
                'id' => $profile->id,
                'slug' => $profile->slug,
                'entity_type' => $profile->entity_type,
                'avatar_color' => $profile->avatar_color,
                'status' => $profile->status,
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
