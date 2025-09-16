<?php

namespace App\Providers;

use App\Notifications\JobFailedAlert;
use App\Services\Campaign\CampaignService;
use App\Services\Campaign\CampaignServiceInterface;
use App\Services\Campaign\DonationIntentService;
use App\Services\Campaign\DonationIntentServiceInterface;
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
        $this->app->bind(CampaignServiceInterface::class, CampaignService::class);
        $this->app->bind(DonationIntentServiceInterface::class, DonationIntentService::class);
        $this->app->bind(
            \App\Services\Comment\Contracts\CommentService::class,
            \App\Services\Comment\CommentService::class
        );
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
