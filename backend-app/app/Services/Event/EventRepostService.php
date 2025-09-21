<?php

declare(strict_types=1);

namespace App\Services\Event;

use App\Enums\Share\ShareChannel;
use App\Models\Event\Event;
use App\Models\Post\Post;
use App\Models\User;
use App\Services\Share\Contracts\ShareGuard;
use App\Services\Share\Contracts\ShareService;
use Illuminate\Support\Facades\DB;

class EventRepostService
{
    public function __construct(
        protected ShareGuard $guard,
        protected ShareService $shareService
    ) {}

    public function create(User $actor, Event $event, ?string $text = null, ?string $visibility = 'public'): Post
    {
        if (! $this->guard->canShare($event, $actor, ShareChannel::REPOST)) {
            abort(403);
        }

        return DB::transaction(function () use ($actor, $event, $text, $visibility) {
            $post = new Post;
            $post->user_id = $actor->getKey();
            $post->content = $text ?? '';
            $post->visibility = $visibility ?? 'public';
            $post->status = Post::STATUS_PUBLISHED;
            $post->published_at = now();
            $post->save();

            $post->postable()->associate($event);
            $post->save();

            $this->shareService->create(
                $event,
                ShareChannel::REPOST,
                $actor->getKey(),
                [
                    'utm_source' => 'repost',
                    'utm_campaign' => 'event_'.$event->getKey(),
                ],
                null,
                true
            );

            return $post;
        });
    }
}
