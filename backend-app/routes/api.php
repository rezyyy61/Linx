<?php

use App\Http\Controllers\Api\Announcement\AnnouncementController;
use App\Http\Controllers\Api\Campaign\CampaignController;
use App\Http\Controllers\Api\Event\EventController;
use App\Http\Controllers\Api\Event\EventJoinController;
use App\Http\Controllers\Api\Follow\FollowController;
use App\Http\Controllers\Api\Follow\FollowRequestController;
use App\Http\Controllers\Api\media\MediaAttachController;
use App\Http\Controllers\Api\media\MediaController;
use App\Http\Controllers\Api\Member\ContentController;
use App\Http\Controllers\Api\Member\ContentTargetController;
use App\Http\Controllers\Api\Member\MembershipController;
use App\Http\Controllers\Api\Member\MyMembershipController;
use App\Http\Controllers\Api\Notifications\NotificationController;
use App\Http\Controllers\Api\Post\PostController;
use App\Http\Controllers\Api\Post\RepostController;
use App\Http\Controllers\Api\Profile\LinkController;
use App\Http\Controllers\Api\Profile\ProfileController;
use App\Http\Controllers\Api\Profile\ValueController;
use App\Http\Controllers\Api\Share\ShareController;
use App\Http\Controllers\Api\User\UserSearchController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
    ->prefix('media')
    ->group(function () {
        Route::post('presigned', [MediaController::class, 'createPresignedUrl']);
        Route::post('{media}/finalize', [MediaController::class, 'finalizeUpload']);
        Route::post('/{media}/attach', [MediaAttachController::class, 'attach']);
        Route::post('/{media}/detach', [MediaAttachController::class, 'detach']);
        Route::post('/{media}/attach-single', [MediaAttachController::class, 'attachSingle']);
        Route::get('{media}', [MediaController::class, 'show']);
        Route::delete('{media}', [MediaController::class, 'destroy']);
    });

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('posts', PostController::class)->parameters([
        'posts' => 'post',
    ]);
    Route::post('/posts/{post}/repost', [RepostController::class, 'store'])->name('posts.repost.store');

    Route::post('/reposts', [RepostController::class, 'storeGeneric'])->name('reposts.store');
});

Route::middleware('auth:sanctum')
    ->prefix('profile/me')
    ->as('profile.me.')
    ->group(function () {

        // Me
        Route::get('/', [ProfileController::class, 'me'])->name('show');
        Route::patch('/', [ProfileController::class, 'updateMe'])->name('update');
        Route::put('/', [ProfileController::class, 'updateMe'])->name('replace');

        // Media (Logo + Files)
        Route::prefix('')->as('media.')->group(function () {
            Route::post('/logo', [MediaController::class, 'setLogo'])->name('logo.set');
            Route::delete('/logo', [MediaController::class, 'clearLogo'])->name('logo.clear');

            Route::prefix('files')->as('files.')->group(function () {
                Route::get('/', [MediaController::class, 'files'])->name('index');
                Route::post('/', [MediaController::class, 'addFile'])->name('store');
                Route::delete('/{mediaId}', [MediaController::class, 'removeFile'])
                    ->whereUuid('mediaId')
                    ->name('destroy');
            });
        });

        // Links
        Route::prefix('links')->as('links.')->group(function () {
            Route::get('/', [LinkController::class, 'index'])->name('index');
            Route::post('/', [LinkController::class, 'store'])->name('store');
            Route::patch('/{id}', [LinkController::class, 'update'])->whereNumber('id')->name('update');
            Route::put('/{id}', [LinkController::class, 'update'])->whereNumber('id')->name('replace');
            Route::delete('/{id}', [LinkController::class, 'destroy'])->whereNumber('id')->name('destroy');
            Route::put('/reorder', [LinkController::class, 'reorder'])->name('reorder');
        });

        // Values
        Route::prefix('values')->as('values.')->group(function () {
            Route::get('/', [ValueController::class, 'index'])->name('index');
            Route::post('/', [ValueController::class, 'store'])->name('store');
            Route::patch('/{id}', [ValueController::class, 'update'])->whereNumber('id')->name('update');
            Route::put('/{id}', [ValueController::class, 'update'])->whereNumber('id')->name('replace');
            Route::delete('/{id}', [ValueController::class, 'destroy'])->whereNumber('id')->name('destroy');
            Route::put('/reorder', [ValueController::class, 'reorder'])->name('reorder');
        });
    });

Route::middleware('auth:sanctum')->apiResource('events', EventController::class)->names('events');
Route::middleware('auth:sanctum')->group(function () {
    Route::post('events/{event}/join', [EventJoinController::class, 'join']);
    Route::delete('events/{event}/join', [EventJoinController::class, 'unjoin']);
    Route::get('events/{event}/join', [EventJoinController::class, 'status']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/follow/{user}', [FollowRequestController::class, 'store']);
    Route::delete('/follow/{user}', [FollowController::class, 'destroy']);
    Route::get('/follow/requests', [FollowRequestController::class, 'index']);
    Route::get('/follow/requests/outgoing', [FollowRequestController::class, 'outgoing']);
    Route::post('/follow/requests/{followRequest}/accept', [FollowRequestController::class, 'accept']);
    Route::post('/follow/requests/{followRequest}/reject', [FollowRequestController::class, 'reject']);
    Route::post('/follow/requests/{followRequest}/cancel', [FollowRequestController::class, 'cancel']);
});

Route::get('/users/{user}/followers', [FollowController::class, 'followers']);
Route::get('/users/{user}/followings', [FollowController::class, 'followings']);
Route::get('/users/{user}/suggestions', [FollowController::class, 'suggestions']);
Route::get('/users/{user}/mutuals', [FollowController::class, 'mutuals']);
Route::get('/profile/me-lite', [ProfileController::class, 'meLite']);
Route::get('/users/search', [UserSearchController::class, 'index']);

Route::middleware('auth:sanctum')->prefix('notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::post('{id}/read', [NotificationController::class, 'read'])->whereNumber('id');
    Route::post('read-all', [NotificationController::class, 'readAll']);
    Route::delete('{id}', [NotificationController::class, 'destroy'])->whereNumber('id');
});
Route::middleware('auth:sanctum')->apiResource('announcements', AnnouncementController::class);

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('users/{user}/members')->group(function () {
        Route::get('/', [MembershipController::class, 'index']);
        Route::post('/', [MembershipController::class, 'store']);
    });

    Route::get('/users/{user}/my-memberships', [MyMembershipController::class, 'index']);
    Route::patch('/my-memberships/{membership}', [MyMembershipController::class, 'update']);
    Route::post('/my-memberships/{membership}/leave', [MyMembershipController::class, 'leave']);

    Route::prefix('memberships/{membership}')->group(function () {
        Route::post('/accept', [MembershipController::class, 'accept']);
        Route::post('/reject', [MembershipController::class, 'reject']);
        Route::delete('/', [MembershipController::class, 'destroy']);
    });

    Route::prefix('users/{user}/member-contents')->group(function () {
        Route::get('/', [ContentController::class, 'index']);
        Route::post('/', [ContentController::class, 'store']);
    });

    Route::prefix('member-contents/{content}')->group(function () {
        Route::put('/', [ContentController::class, 'update']);
        Route::delete('/', [ContentController::class, 'destroy']);
        Route::post('/schedule', [ContentController::class, 'schedule']);

        Route::get('/targets', [ContentTargetController::class, 'index']);
    });

    Route::prefix('member-content-targets/{target}')->group(function () {
        Route::post('/sent', [ContentTargetController::class, 'markSent']);
        Route::post('/failed', [ContentTargetController::class, 'markFailed']);
        Route::post('/opened', [ContentTargetController::class, 'markOpened']);
        Route::post('/clicked', [ContentTargetController::class, 'markClicked']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/share', [ShareController::class, 'store'])->name('share.store');
});

Route::middleware('auth:sanctum')->apiResource('campaigns', CampaignController::class)->names('campaigns');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('campaigns/{campaign}/publish', [CampaignController::class, 'publish'])->name('campaigns.publish');
});
