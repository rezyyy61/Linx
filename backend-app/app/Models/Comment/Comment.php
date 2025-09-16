<?php

namespace App\Models\Comment;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'commentable_type',
        'commentable_id',
        'user_id',
        'parent_id',
        'root_id',
        'depth',
        'path',
        'body',
        'status',
        'replies_count',
        'reactions_count',
    ];

    protected $casts = [
        'depth' => 'int',
        'replies_count' => 'int',
        'reactions_count' => 'int',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if ($model->parent_id) {
                $parent = self::query()->select('id', 'depth', 'root_id', 'path')->find($model->parent_id);
                if ($parent) {
                    $model->root_id = $parent->root_id ?: $parent->id;
                    $model->depth = ($parent->depth ?? 0) + 1;
                }
            } else {
                $model->depth = 0;
            }
            if (! $model->status) {
                $model->status = 'visible';
            }
            if ($model->path === null) {
                $model->path = '';
            }
        });

        static::created(function (self $model): void {
            $pad = str_pad((string) $model->id, 6, '0', STR_PAD_LEFT);
            if ($model->parent_id) {
                $parent = self::query()->select('id', 'path', 'root_id')->find($model->parent_id);
                $model->path = $parent ? ($parent->path ? $parent->path.'/'.$pad : $pad) : $pad;
                $model->root_id = $model->root_id ?: ($parent ? ($parent->root_id ?: $parent->id) : $model->id);
                if ($parent) {
                    $parent->increment('replies_count');
                }
            } else {
                $model->path = $pad;
                $model->root_id = $model->id;
            }
            $model->saveQuietly();
        });
    }

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function root(): BelongsTo
    {
        return $this->belongsTo(self::class, 'root_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function scopeVisible($q)
    {
        return $q->where('status', 'visible');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(CommentLike::class);
    }

    public function likers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'comment_likes', 'comment_id', 'user_id')->withTimestamps();
    }

    protected static function newFactory()
    {
        return \Database\Factories\Comment\CommentFactory::new();
    }
}
