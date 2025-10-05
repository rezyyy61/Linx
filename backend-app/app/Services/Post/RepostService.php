<?php

declare(strict_types=1);

namespace App\Services\Post;

use App\Enums\Share\ShareChannel;
use App\Models\Announcement\Announcement;
use App\Models\Campaign\Campaign;
use App\Models\Event\Event;
use App\Models\Post\Post;
use App\Models\Publication\Publication;
use App\Models\Share\Contracts\Shareable as ShareableContract;
use App\Models\User;
use App\Services\Share\Contracts\ShareGuard;
use App\Services\Share\Contracts\ShareService;
use Illuminate\Database\Eloquent\Model;
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

        return $this->createForShareable($actor, $target, $text, $visibility);
    }

    public function createForShareable(User $actor, Model&ShareableContract $target, ?string $text = null, ?string $visibility = 'public'): Post
    {
        if (! $this->guard->canShare($target, $actor, ShareChannel::REPOST)) {
            abort(403);
        }

        return DB::transaction(function () use ($actor, $target, $text, $visibility) {
            if ($target instanceof Post) {
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
            }

            if ($target instanceof Event) {
                $post = new Post;
                $post->user_id = $actor->getKey();
                $post->content = $text ?? '';
                $post->visibility = $visibility ?? 'public';
                $post->status = Post::STATUS_PUBLISHED;
                $post->published_at = now();
                $post->save();

                $post->postable()->associate($target);
                $post->save();

                $this->shareService->create(
                    $target,
                    ShareChannel::REPOST,
                    $actor->getKey(),
                    [
                        'utm_source' => 'repost',
                        'utm_campaign' => 'event_'.$target->getKey(),
                    ],
                    null,
                    true
                );

                return $post;
            }

            if ($target instanceof Announcement) {
                $post = new Post;
                $post->user_id = $actor->getKey();
                $post->content = $text ?? '';
                $post->visibility = $visibility ?? 'public';
                $post->status = Post::STATUS_PUBLISHED;
                $post->published_at = now();
                $post->save();

                $post->postable()->associate($target);
                $post->save();

                $this->shareService->create(
                    $target,
                    ShareChannel::REPOST,
                    $actor->getKey(),
                    [
                        'utm_source' => 'repost',
                        'utm_campaign' => 'announcement_'.$target->getKey(),
                    ],
                    null,
                    true
                );

                return $post;
            }

            if ($target instanceof Campaign) {
                $post = new Post;
                $post->user_id = $actor->getKey();
                $post->content = $text ?? '';
                $post->visibility = $visibility ?? 'public';
                $post->status = Post::STATUS_PUBLISHED;
                $post->published_at = now();
                $post->save();

                $post->postable()->associate($target);
                $post->save();

                $this->shareService->create(
                    $target,
                    ShareChannel::REPOST,
                    $actor->getKey(),
                    [
                        'utm_source' => 'repost',
                        'utm_campaign' => 'campaign_'.$target->getKey(),
                    ],
                    null,
                    true
                );

                return $post;
            }

            if ($target instanceof Publication) {
                $post = new Post;
                $post->user_id = $actor->getKey();
                $post->content = $text ?? '';
                $post->visibility = $visibility ?? 'public';
                $post->status = Post::STATUS_PUBLISHED;
                $post->published_at = now();
                $post->save();

                $post->postable()->associate($target);
                $post->save();

                $this->shareService->create(
                    $target,
                    ShareChannel::REPOST,
                    $actor->getKey(),
                    [
                        'utm_source' => 'repost',
                        'utm_campaign' => 'publication_'.$target->getKey(),
                    ],
                    null,
                    true
                );

                return $post;
            }

            abort(422, 'Unsupported shareable type for repost');
        });
    }
}
