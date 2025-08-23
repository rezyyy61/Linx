<?php

declare(strict_types=1);

namespace App\Models\Post;

use App\Contracts\Mediable;
use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model implements Mediable
{
    use HasFactory;
    use SoftDeletes;

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
}
