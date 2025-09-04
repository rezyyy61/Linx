<?php

namespace App\Policies\Event;

use App\Models\Event\Event;
use App\Models\User;

class EventPolicy
{
    private function isAdmin(User $user): bool
    {
        return (method_exists($user, 'isAdmin') && $user->isAdmin())
            || (bool) data_get($user, 'is_admin', false);
    }

    private function owns(User $user, Event $event): bool
    {
        return (int) $event->organizer_id === (int) $user->id;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Event $event): bool
    {
        return (bool) $event->is_published || $this->owns($user, $event) || $this->isAdmin($user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Event $event): bool
    {
        return $this->owns($user, $event) || $this->isAdmin($user);
    }

    public function delete(User $user, Event $event): bool
    {
        return $this->owns($user, $event) || $this->isAdmin($user);
    }

    public function manageMedia(User $user, Event $event): bool
    {
        return $this->owns($user, $event) || $this->isAdmin($user);
    }
}
