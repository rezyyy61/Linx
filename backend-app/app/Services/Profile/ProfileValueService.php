<?php

namespace App\Services\Profile;

use App\Models\Profile\Profile;
use App\Models\Profile\ProfileValue;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ProfileValueService
{
    public function list(User $user): Collection
    {
        $profileId = (int) Profile::query()
            ->where('user_id', $user->getKey())
            ->value('id');

        return ProfileValue::query()
            ->where('profile_id', $profileId)
            ->orderBy('order')
            ->get();
    }

    public function create(User $user, array $data): ProfileValue
    {
        return DB::transaction(function () use ($user, $data) {
            $profile = Profile::query()
                ->where('user_id', $user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $nextOrder = (int) ProfileValue::query()
                    ->where('profile_id', $profile->getKey())
                    ->max('order') + 1;

            $payload = [
                'profile_id' => $profile->getKey(),
                'type' => $data['type'],
                'value' => trim($data['value']),
                'order' => $data['order'] ?? $nextOrder,
            ];

            return ProfileValue::create($payload);
        });
    }

    public function update(User $user, int $id, array $data): ProfileValue
    {
        return DB::transaction(function () use ($user, $id, $data) {
            $value = $this->findOwnedValue($user, $id);

            $payload = collect($data)->only(['type', 'value', 'order'])->toArray();
            if (array_key_exists('value', $payload)) {
                $payload['value'] = trim((string) $payload['value']);
            }

            $value->fill($payload);
            $value->save();

            return $value;
        });
    }

    public function delete(User $user, int $id): void
    {
        DB::transaction(function () use ($user, $id) {
            $value = $this->findOwnedValue($user, $id);
            $value->delete();
        });
    }

    public function reorder(User $user, array $ids): Collection
    {
        return DB::transaction(function () use ($user, $ids) {
            $profile = Profile::query()
                ->where('user_id', $user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $ownedIds = ProfileValue::query()
                ->where('profile_id', $profile->getKey())
                ->pluck('id')
                ->all();

            foreach ($ids as $idx => $id) {
                if (in_array($id, $ownedIds, true)) {
                    ProfileValue::where('id', $id)->update(['order' => $idx]);
                }
            }

            return ProfileValue::query()
                ->where('profile_id', $profile->getKey())
                ->orderBy('order')
                ->get();
        });
    }

    private function findOwnedValue(User $user, int $id): ProfileValue
    {
        $profileId = (int) Profile::query()
            ->where('user_id', $user->getKey())
            ->value('id');

        return ProfileValue::query()
            ->where('id', $id)
            ->where('profile_id', $profileId)
            ->firstOrFail();
    }
}
