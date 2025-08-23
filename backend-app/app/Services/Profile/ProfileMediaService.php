<?php

namespace App\Services\Profile;

use App\Enums\MediaType;
use App\Models\Media;
use App\Models\Profile\Profile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProfileMediaService
{
    public function setLogo(User $user, int $mediaId): Profile
    {
        return DB::transaction(function () use ($user, $mediaId) {
            $profile = Profile::query()
                ->where('user_id', $user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $media = Media::query()->findOrFail($mediaId);
            if ($media->type !== MediaType::IMAGE) {
                abort(422, 'Logo must be an image.');
            }

            $profile->media()->wherePivot('collection', 'logo')->detach();
            $profile->media()->attach($media->getKey(), ['collection' => 'logo', 'order_column' => 0]);

            $profile->load(['media', 'logo']);

            return $profile;
        });
    }

    public function clearLogo(User $user): Profile
    {
        return DB::transaction(function () use ($user) {
            $profile = Profile::query()
                ->where('user_id', $user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $profile->media()->wherePivot('collection', 'logo')->detach();

            $profile->load(['media', 'logo']);

            return $profile;
        });
    }

    public function addFile(User $user, int $mediaId, ?int $order = null): Profile
    {
        return DB::transaction(function () use ($user, $mediaId, $order) {
            $profile = Profile::query()
                ->where('user_id', $user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $media = Media::query()->findOrFail($mediaId);
            if ($media->type !== MediaType::DOCUMENT) {
                abort(422, 'Only document files are allowed.');
            }

            $next = $order ?? ((int) $profile->media()
                    ->wherePivot('collection', 'documents')
                    ->max('mediables.order_column') + 1);

            $profile->media()->attach($media->getKey(), ['collection' => 'documents', 'order_column' => $next]);

            $profile->load('media');

            return $profile;
        });
    }

    public function removeFile(User $user, int $mediaId): Profile
    {
        return DB::transaction(function () use ($user, $mediaId) {
            $profile = Profile::query()
                ->where('user_id', $user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $profile->media()
                ->wherePivot('collection', 'documents')
                ->detach($mediaId);

            $profile->load('media');

            return $profile;
        });
    }

    public function listFiles(User $user)
    {
        $profile = Profile::query()
            ->where('user_id', $user->getKey())
            ->firstOrFail();

        return $profile->media()
            ->wherePivot('collection', 'documents')
            ->orderBy('mediables.order_column')
            ->get();
    }
}
