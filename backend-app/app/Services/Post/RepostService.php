<?php

declare(strict_types=1);

namespace App\Services\Post;

use App\Enums\Share\ShareChannel;
use App\Models\Post\Post;
use App\Models\User;
use App\Services\Share\Contracts\ShareGuard;
use App\Services\Share\Contracts\ShareService;
use Illuminate\Support\Facades\DB;

class RepostService
{
    public function __construct(
        protected ShareGuard $guard,
        protected ShareService $shareService
    ) {}

    public function create(User $actor, Post $original, ?string $text = null, ?string $visibility = 'public'): Post
    {
        $target = $original->root();

        if (! $this->guard->canShare($target, $actor, ShareChannel::REPOST)) {
            abort(403);
        }

        return DB::transaction(function () use ($actor, $target, $text, $visibility) {
            $post = new Post;
            $post->user_id = $actor->getKey();
            $post->content = $text ?? '';
            $post->visibility = $visibility ?? 'public';
            $post->status = Post::STATUS_PUBLISHED;
            $post->published_at = now();
            $post->repost_of_id = $target->getKey();
            $post->save();

            $this->shareService->create(
                $target,
                ShareChannel::REPOST,
                $actor->getKey(),
                [
                    'utm_source' => 'repost',
                    'utm_campaign' => 'post_'.$target->getKey(),
                ],
                null,
                true
            );

            return $post;
        });
    }
}
