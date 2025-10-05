<?php

namespace App\Models\Publication;

use App\Contracts\Mediable;
use App\Contracts\PostableResourceable;
use App\Http\Resources\PublicApi\PublicMiniUserResource;
use App\Models\Media;
use App\Models\Share\Concerns\IsShareable;
use App\Models\Share\Contracts\Shareable as ShareableContract;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;

class Publication extends Model implements Mediable, PostableResourceable, ShareableContract
{
    use HasFactory;
    use IsShareable;

    protected $table = 'publications';

    protected $fillable = [
        'title',
        'issue',
        'description',
        'owner_id',
        'slug',
        'is_published',
        'publish_at',
        'language',
    ];

    protected $casts = [
        'owner_id' => 'integer',
        'is_published' => 'boolean',
        'publish_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $m) {
            if (empty($m->slug)) {
                $m->slug = static::uniqueSlug($m->title);
            }
            if (empty($m->language)) {
                $m->language = config('app.locale', 'fa');
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
        $base = Str::slug($title) ?: 'publication';
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

    public function media(): MorphToMany
    {
        return $this->morphToMany(Media::class, 'mediable', 'mediables')
            ->withPivot(['collection', 'order_column'])
            ->withTimestamps()
            ->orderBy('mediables.order_column');
    }

    public function covers(): MorphToMany
    {
        return $this->media()->wherePivot('collection', 'publication-cover');
    }

    public function documents(): MorphToMany
    {
        return $this->media()->wherePivot('collection', 'publication-document');
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

    public function owner(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->when(now(), fn ($q) => $q->where(function ($qq) {
                $qq->whereNull('publish_at')->orWhere('publish_at', '<=', now());
            }));
    }

    public function scopeSearch($query, ?string $term)
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('title', 'like', '%'.$term.'%')
                ->orWhere('issue', 'like', '%'.$term.'%')
                ->orWhere('description', 'like', '%'.$term.'%');
        });
    }

    public function getShareUrl(): string
    {
        return url('/publications/'.$this->slug);
    }

    public function toPostableResource(): array
    {
        return [
            'id' => (int) $this->getKey(),
            'slug' => (string) ($this->slug ?? ''),
            'title' => (string) ($this->title ?? ''),
            'issue' => (string) ($this->issue ?? ''),
            'description' => (string) ($this->description ?? ''),
            'cover_url' => $this->cover_url ?? null,
            'is_published' => (bool) ($this->is_published ?? false),
            'publish_at' => optional($this->publish_at)?->toIso8601String(),
            'language' => $this->language ?? null,
            'owner' => $this->relationLoaded('owner') && $this->owner
                ? (new PublicMiniUserResource($this->owner))->toArray(request())
                : null,
        ];
    }

    public function getPostableAlias(): string
    {
        return 'publication';
    }

    public function getPostableSlug(): ?string
    {
        return $this->slug;
    }
}
