<?php

declare(strict_types=1);

namespace App\Policies\Post;

use App\Models\Post\Post;
use App\Models\User;

class PostPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Post $post): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id || $user->can('update-any-post');
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id || $user->can('delete-any-post');
    }
}
