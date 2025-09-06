<?php

namespace App\Providers;

use App\Repositories\Follow\FollowRepository;
use App\Repositories\Follow\FollowRepositoryInterface;
use App\Services\Follow\FollowRequestService;
use App\Services\Follow\FollowRequestServiceInterface;
use App\Services\Follow\FollowService;
use App\Services\Follow\FollowServiceInterface;
use Illuminate\Support\ServiceProvider;

class FollowServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(FollowRepositoryInterface::class, FollowRepository::class);
        $this->app->bind(FollowServiceInterface::class, FollowService::class);
        $this->app->bind(FollowRequestServiceInterface::class, FollowRequestService::class);
    }

    public function boot(): void {}
}
