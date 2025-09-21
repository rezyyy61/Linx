<?php

declare(strict_types=1);

namespace App\Services\Event;

use App\Models\Event\Event;
use Illuminate\Support\Facades\Auth;

class EventJoinService
{
    public function join(Event $event): void
    {
        $user = Auth::user();
        if (! $user) {
            throw new \RuntimeException('Must be authenticated to join an event.');
        }

        $event->participants()->syncWithoutDetaching([$user->id]);
    }

    public function unjoin(Event $event): void
    {
        $user = Auth::user();
        if (! $user) {
            throw new \RuntimeException('Must be authenticated to unjoin an event.');
        }

        $event->participants()->detach($user->id);
    }

    public function hasJoined(Event $event): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        return $event->participants()->where('user_id', $user->id)->exists();
    }

    public function count(Event $event): int
    {
        return $event->participants()->count();
    }
}
