<?php

namespace App\Providers;

use App\Notifications\JobFailedAlert;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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
