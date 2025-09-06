<?php

namespace App\Services\Notifications;

use App\Models\Notifications\Notification as NotificationModel;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class NotificationService
{
    public function paginateFor(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return NotificationModel::query()->where('user_id', $user->id)->latest()->paginate($perPage);
    }

    public function markAsRead(User $user, int $id): bool
    {
        $n = NotificationModel::query()->where('user_id', $user->id)->findOrFail($id);
        $n->read_at = now();

        return $n->save();
    }

    public function markAllAsRead(User $user): int
    {
        return NotificationModel::query()->where('user_id', $user->id)->whereNull('read_at')->update(['read_at' => now()]);
    }

    public function delete(User $user, int $id): bool
    {
        $n = NotificationModel::query()->where('user_id', $user->id)->findOrFail($id);

        return (bool) $n->delete();
    }
}
