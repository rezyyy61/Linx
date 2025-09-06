<?php

namespace App\Models;

use App\Contracts\Mediable;
use App\Models\Concerns\Followable;
use App\Models\Concerns\HasMedia;
use App\Models\Profile\Profile;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property-read \App\Models\Profile\Profile|null $profile
 * @property-read \Illuminate\Database\Eloquent\Collection<int,\App\Models\User> $followers
 * @property-read \Illuminate\Database\Eloquent\Collection<int,\App\Models\User> $followings
 */
class User extends Authenticatable implements Mediable, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use Followable, HasFactory, HasMedia, Notifiable;

    /** @var list<string> */
    protected $fillable = ['name', 'email', 'password'];

    /** @var list<string> */
    protected $hidden = ['password', 'remember_token'];

    /** @return array<string,string> */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\QueuedResetPassword($token));
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'follows',
            'followed_id',
            'follower_id'
        )->withTimestamps();
    }

    public function followings(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'follows',
            'follower_id',
            'followed_id'
        )->withTimestamps();
    }

    public function followRequestsSent(): HasMany
    {
        return $this->hasMany(\App\Models\Follow\FollowRequest::class, 'actor_id');
    }

    public function followRequestsReceived(): HasMany
    {
        return $this->hasMany(\App\Models\Follow\FollowRequest::class, 'target_id');
    }
}
