<?php

use App\Http\Controllers\Api\Comment\CommentController;
use App\Http\Controllers\Api\Comment\CommentLikeController;
use App\Http\Controllers\Api\Public\Post\PostActionController;
use App\Http\Controllers\Api\Public\Post\PublicPostController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/posts', [PublicPostController::class, 'index']);
    Route::get('/posts/{id}', [PublicPostController::class, 'show']);

    Route::get('/posts/{post}/likes/preview', [PostActionController::class, 'likesPreview']);
    Route::get('/posts/{post}/likes', [PostActionController::class, 'likes']);

    Route::get('/comments', [CommentController::class, 'index']);
    Route::get('/comments/{parentId}/children', [CommentController::class, 'children']);
    Route::get('/comments/{commentId}/likes', [CommentLikeController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/posts/{post}/like', [PostActionController::class, 'toggleLike']);
        Route::post('/posts/{post}/save', [PostActionController::class, 'toggleSave']);

        Route::post('/comments', [CommentController::class, 'store']);
        Route::patch('/comments/{id}', [CommentController::class, 'update']);
        Route::delete('/comments/{id}', [CommentController::class, 'destroy']);
        Route::post('/comments/{commentId}/like', [CommentLikeController::class, 'toggle']);
    });
});
