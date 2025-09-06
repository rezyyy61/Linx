<?php

namespace App\Models\Profile;

use App\Contracts\Mediable;
use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Profile extends Model implements Mediable
{
    protected $fillable = [
        'slug',
        'entity_type',
        'location',
        'founded_year',
        'status',
        'verified',
        'published_at',
        'user_id',
        'avatar_color',
    ];

    protected $casts = [
        'verified' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function media(): MorphToMany
    {
        return $this->morphToMany(Media::class, 'mediable', 'mediables')
            ->withPivot('collection', 'order_column')
            ->withTimestamps()
            ->orderBy('mediables.order_column');
    }

    public function logo(): MorphToMany
    {
        return $this->media()->wherePivot('collection', 'logo');
    }

    public function cover(): MorphToMany
    {
        return $this->media()->wherePivot('collection', 'cover');
    }

    public function gallery(): MorphToMany
    {
        return $this->media()->wherePivot('collection', 'gallery');
    }

    public function documents(): MorphToMany
    {
        return $this->media()->wherePivot('collection', 'document');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ProfileTranslation::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(ProfileLink::class)->orderBy('order');
    }

    public function values(): HasMany
    {
        return $this->hasMany(ProfileValue::class)->orderBy('order');
    }
}
