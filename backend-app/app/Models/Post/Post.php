<?php

declare(strict_types=1);

namespace App\Models\Post;

use App\Contracts\Mediable;
use App\Models\Comment\Comment;
use App\Models\Media;
use App\Models\Share\Concerns\IsShareable;
use App\Models\Share\Contracts\Shareable as ShareableContract;
use App\Models\Share\Share;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model implements Mediable, ShareableContract
{
    use HasFactory;
    use IsShareable;
    use SoftDeletes;

    public const STATUS_PUBLISHED = 'published';

    public const MEDIA_COLLECTION = 'post';

    protected $fillable = [
        'user_id',
        'content',
        'visibility',
        'status',
        'published_at',
        'repost_of_id',
        'share_id',
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

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function rootComments(): MorphMany
    {
        return $this->comments()->whereNull('parent_id');
    }

    public function share(): BelongsTo
    {
        return $this->belongsTo(Share::class, 'share_id');
    }

    public function postable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getShareUrl(): string
    {
        return url('/posts/'.$this->getKey());
    }

    public function original(): BelongsTo
    {
        return $this->belongsTo(self::class, 'repost_of_id');
    }

    public function reposts()
    {
        return $this->hasMany(self::class, 'repost_of_id');
    }

    public function shares()
    {
        return $this->morphMany(Share::class, 'shareable');
    }

    public function root(): self
    {
        $p = $this;
        while ($p->repost_of_id) {
            $rel = $p->getRelationValue('original');
            $next = $rel instanceof self ? $rel : $p->original()->first();
            if (! $next || $next->id === $p->id) {
                break;
            }
            $p = $next;
        }

        return $p;
    }
}
