<?php

use App\Http\Controllers\Api\Public\Post\CommentActionController;
use App\Http\Controllers\Api\Public\Post\CommentController;
use App\Http\Controllers\Api\Public\Post\PostActionController;
use App\Http\Controllers\Api\Public\Post\PublicPostController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/posts', [PublicPostController::class, 'index']);
    Route::get('/posts/{id}', [PublicPostController::class, 'show']);

    Route::get('/posts/{post}/likes/preview', [PostActionController::class, 'likesPreview']);
    Route::get('/posts/{post}/likes', [PostActionController::class, 'likes']);

    Route::get('posts/{post}/comments', [CommentController::class, 'index']);
    Route::get('posts/{post}/comments/roots', [CommentController::class, 'roots']);
    Route::get('comments/{comment}/replies', [CommentController::class, 'replies']);
    Route::get('posts/{post}/comments/{comment}/likes', [CommentActionController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/posts/{post}/like', [PostActionController::class, 'toggleLike']);
        Route::post('posts/{post}/comments', [CommentController::class, 'store']);
        Route::patch('posts/{post}/comments/{comment}', [CommentController::class, 'update']);
        Route::delete('posts/{post}/comments/{comment}', [CommentController::class, 'destroy']);
        Route::post('posts/{post}/comments/{comment}/like', [CommentActionController::class, 'toggleLike']);
        Route::post('/posts/{post}/save', [PostActionController::class, 'toggleSave']);
    });
});
