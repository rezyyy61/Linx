<?php

namespace App\Services\Profile;

use App\Models\Profile\Profile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProfileUpdateService
{
    public function update(User $user, array $data): Profile
    {
        return DB::transaction(function () use ($user, $data) {
            $profile = Profile::query()
                ->where('user_id', $user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $updatable = collect($data)->only([
                'slug',
                'location',
                'founded_year',
                'avatar_color',
            ])->toArray();

            if ($updatable !== []) {
                $profile->fill($updatable);
                $profile->save();
            }

            if (isset($data['translation']) && is_array($data['translation'])) {
                $t = $data['translation'];
                $locale = $t['locale'] ?? app()->getLocale();

                $profile->translations()->updateOrCreate(
                    ['locale' => $locale],
                    [
                        'tagline' => $t['tagline'] ?? null,
                        'about' => $t['about'] ?? null,
                        'goals' => $t['goals'] ?? null,
                        'activities' => $t['activities'] ?? null,
                        'structure' => $t['structure'] ?? null,
                    ]
                );
            }

            $profile->load(['translations', 'links', 'values', 'media']);

            return $profile;
        });
    }
}
