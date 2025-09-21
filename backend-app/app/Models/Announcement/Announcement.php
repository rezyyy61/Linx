<?php

namespace App\Models\Announcement;

use App\Contracts\Mediable;
use App\Models\Media;
use App\Models\Share\Concerns\IsShareable;
use App\Models\Share\Contracts\Shareable;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;

class Announcement extends Model implements Mediable, Shareable
{
    use HasFactory;
    use IsShareable;

    protected $table = 'announcements';

    protected $fillable = [
        'owner_id',
        'title',
        'body',
        'slug',
        'is_pinned',
        'visibility',
        'publish_at',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'publish_at' => 'datetime',
    ];

    protected $appends = [
        'cover_url',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $m) {
            if (empty($m->slug)) {
                $m->slug = static::uniqueSlug($m->title);
            }
        });

        static::updating(function (self $m) {
            if (empty($m->slug) && ! empty($m->title)) {
                $m->slug = static::uniqueSlug($m->title, $m->id);
            }
        });
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'announcement';
        $slug = $base;
        $i = 2;
        while (
            static::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function media(): MorphToMany
    {
        return $this->morphToMany(Media::class, 'mediable', 'mediables')
            ->withPivot(['collection', 'order_column'])
            ->withTimestamps()
            ->orderBy('mediables.order_column');
    }

    public function covers(): MorphToMany
    {
        return $this->media()->wherePivot('collection', 'announcement-cover');
    }

    public function documents(): MorphToMany
    {
        return $this->media()->wherePivot('collection', 'announcement-document');
    }

    public function getCoverUrlAttribute(): ?string
    {
        if ($this->relationLoaded('covers')) {
            $m = $this->covers->first();

            return $m instanceof Media ? $m->publicUrl() : null;
        }

        $m = $this->covers()->first();

        return $m instanceof Media ? $m->publicUrl() : null;
    }

    public function getShareUrl(): string
    {
        return url('/announcements/'.$this->slug);
    }
}
