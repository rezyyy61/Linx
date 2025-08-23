<?php

namespace App\Services\Profile;

use App\Models\Profile\ProfileLink;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ProfileLinkService
{
    public function list(User $user): Collection
    {
        $profileId = (int) $user->profile()->value('id');

        return ProfileLink::query()
            ->where('profile_id', $profileId)
            ->orderBy('order')
            ->get();
    }

    public function create(User $user, array $data): ProfileLink
    {
        return DB::transaction(function () use ($user, $data) {
            $profileId = (int) $user->profile()->lockForUpdate()->value('id');

            $nextOrder = (int) ProfileLink::query()
                    ->where('profile_id', $profileId)
                    ->max('order') + 1;

            $payload = [
                'profile_id' => $profileId,
                'type' => $data['type'],
                'title' => $data['title'] ?? null,
                'value' => $data['value'] ?? null,
                'url' => $data['url'] ?? null,
                'order' => $data['order'] ?? $nextOrder,
            ];

            return ProfileLink::create($payload);
        });
    }

    public function update(User $user, int $id, array $data): ProfileLink
    {
        return DB::transaction(function () use ($user, $id, $data) {
            $link = $this->findOwnedLink($user, $id);
            $link->fill(collect($data)->only(['type', 'title', 'value', 'url', 'order'])->toArray());
            $link->save();

            return $link;
        });
    }

    public function delete(User $user, int $id): void
    {
        DB::transaction(function () use ($user, $id) {
            $link = $this->findOwnedLink($user, $id);
            $link->delete();
        });
    }

    public function reorder(User $user, array $ids): Collection
    {
        return DB::transaction(function () use ($user, $ids) {
            $profileId = (int) $user->profile()->lockForUpdate()->value('id');

            $ownedIds = ProfileLink::query()
                ->where('profile_id', $profileId)
                ->pluck('id')
                ->all();

            foreach ($ids as $idx => $id) {
                if (in_array($id, $ownedIds, true)) {
                    ProfileLink::where('id', $id)->update(['order' => $idx]);
                }
            }

            return ProfileLink::query()
                ->where('profile_id', $profileId)
                ->orderBy('order')
                ->get();
        });
    }

    private function findOwnedLink(User $user, int $id): ProfileLink
    {
        $profileId = (int) $user->profile()->value('id');

        return ProfileLink::query()
            ->where('id', $id)
            ->where('profile_id', $profileId)
            ->firstOrFail();
    }
}
