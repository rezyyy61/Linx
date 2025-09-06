<?php

namespace App\Providers;

use App\Models\Event\Event;
use App\Models\Follow\FollowRequest;
use App\Models\Post\Post as PostModel;
use App\Models\User;
use App\Policies\Event\EventPolicy;
use App\Policies\Follow\FollowPolicy;
use App\Policies\Follow\FollowRequestPolicy;
use App\Policies\Post\PostPolicy;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        PostModel::class => PostPolicy::class,
        Event::class => EventPolicy::class,
        User::class => FollowPolicy::class,
        FollowRequest::class => FollowRequestPolicy::class,
    ];

    public function boot(): void
    {
        ResetPassword::createUrlUsing(function ($user, string $token) {
            $base = rtrim(config('app.frontend_url', '/'), '/');
            $query = http_build_query([
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ]);

            return "{$base}/auth/reset-password?{$query}";
        });
    }
}
