<?php

declare(strict_types=1);

namespace App\Services\Share\Guards;

use App\Enums\Share\ShareChannel;
use App\Models\Post\Post;
use App\Models\User;
use App\Services\Share\Contracts\ShareGuard;
use Illuminate\Database\Eloquent\Model;

class DefaultShareGuard implements ShareGuard
{
    public function canShare(Model $shareable, ?User $user, ShareChannel $channel): bool
    {
        if ($shareable instanceof Post) {
            if ($shareable->status !== Post::STATUS_PUBLISHED) {
                return false;
            }
            if ($shareable->published_at === null) {
                return false;
            }
            if ($shareable->published_at->isFuture()) {
                return false;
            }
            if ($shareable->getAttribute('visibility') !== 'public') {
                return false;
            }

            return true;
        }

        return false;
    }
}
