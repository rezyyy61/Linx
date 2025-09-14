<?php

declare(strict_types=1);

namespace App\Models\Post;

use App\Contracts\Mediable;
use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model implements Mediable
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_PUBLISHED = 'published';

    public const MEDIA_COLLECTION = 'post';

    protected $fillable = [
        'user_id',
        'content',
        'visibility',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function media(): MorphToMany
    {
        return $this->morphToMany(Media::class, 'mediable', 'mediables')
            ->withPivot(['collection', 'order_column'])
            ->wherePivot('collection', 'post')
            ->withTimestamps()
            ->orderBy('mediables.order_column');
    }

    public function scopePublished($q)
    {
        return $q->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopePublicVisible($q)
    {
        return $q->where('visibility', 'public');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(PostLike::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
