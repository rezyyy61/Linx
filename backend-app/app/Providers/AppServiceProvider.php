<?php

namespace App\Providers;

use App\Notifications\JobFailedAlert;
use App\Services\Share\Contracts\LinkShortener;
use App\Services\Share\Contracts\ShareGuard;
use App\Services\Share\Contracts\ShareService as ShareServiceContract;
use App\Services\Share\Guards\DefaultShareGuard;
use App\Services\Share\ShareService;
use App\Services\Share\Shorteners\LocalShortener;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Services\Comment\Contracts\CommentService::class,
            \App\Services\Comment\CommentService::class
        );
        $this->app->bind(LinkShortener::class, LocalShortener::class);
        $this->app->bind(ShareServiceContract::class, ShareService::class);
        $this->app->bind(ShareGuard::class, DefaultShareGuard::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('resolve-share', function (Request $request) {
            return [Limit::perMinute(120)->by($request->ip())];
        });

        Queue::failing(function (JobFailed $event) {
            if ($mail = config('alerts.mail')) {
                Notification::route('mail', $mail)->notify(new JobFailedAlert($event));
            }
            if ($hook = config('alerts.slack_webhook')) {
                Notification::route('slack', $hook)->notify(new JobFailedAlert($event));
            }
        });
    }
}
