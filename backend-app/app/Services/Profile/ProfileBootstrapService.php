<?php

namespace App\Services\Profile;

use App\Models\Profile\Profile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProfileBootstrapService
{
    public function createForUser(User $user): Profile
    {
        return DB::transaction(function () use ($user) {
            $slug = $this->uniqueSlug(Str::slug($user->name ?: 'user-'.$user->id));
            $color = $this->avatarColorFrom($user->id.'|'.$user->email);

            $profile = Profile::create([
                'user_id' => $user->id,
                'slug' => $slug,
                'entity_type' => 'individual',
                'status' => 'draft',
                'verified' => false,
                'avatar_color' => $color,
            ]);

            $profile->translations()->create([
                'locale' => config('app.locale', 'en'),
                'tagline' => null,
                'about' => null,
                'goals' => null,
                'activities' => null,
                'structure' => null,
            ]);

            return $profile->fresh(['translations']);
        });
    }

    private function uniqueSlug(string $base): string
    {
        $slug = Str::slug($base) ?: 'user';
        $original = $slug;
        $i = 1;
        while (Profile::where('slug', $slug)->exists()) {
            $slug = $original.'-'.$i++;
        }

        return $slug;
    }

    private function avatarColorFrom(string $seed): string
    {
        $hash = substr(sha1($seed), 0, 6);
        $r = hexdec(substr($hash, 0, 2));
        $g = hexdec(substr($hash, 2, 2));
        $b = hexdec(substr($hash, 4, 2));
        $r = max(32, min(224, $r));
        $g = max(32, min(224, $g));
        $b = max(32, min(224, $b));

        return sprintf('#%02X%02X%02X', $r, $g, $b);
    }
}
