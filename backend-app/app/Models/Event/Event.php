<?php

namespace App\Models\Event;

use App\Contracts\Mediable;
use App\Models\Media;
use App\Models\Share\Concerns\IsShareable;
use App\Models\Share\Contracts\Shareable as ShareableContract;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;

class Event extends Model implements Mediable, ShareableContract
{
    use HasFactory;
    use IsShareable;

    protected $table = 'events';

    protected $fillable = [
        'title',
        'description',
        'starts_at',
        'ends_at',
        'location',
        'capacity',
        'is_published',
        'organizer_id',
        'slug',
        'timezone',
        'publish_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_published' => 'boolean',
        'capacity' => 'integer',
        'organizer_id' => 'integer',
        'publish_at' => 'datetime',
    ];

    protected $appends = [
        'starts_at_local',
        'ends_at_local',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function (self $m) {
            if (empty($m->slug)) {
                $m->slug = static::uniqueSlug($m->title);
            }
            if (empty($m->timezone)) {
                $m->timezone = config('app.timezone', 'Europe/Amsterdam');
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
        $base = Str::slug($title) ?: 'event';
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

    public function getStartsAtLocalAttribute(): ?string
    {
        if (! $this->starts_at) {
            return null;
        }

        $tz = $this->timezone ?: 'UTC';

        return $this->starts_at->copy()->setTimezone($tz)->format('Y-m-d\TH:i');
    }

    public function getEndsAtLocalAttribute(): ?string
    {
        if (! $this->ends_at) {
            return null;
        }

        $tz = $this->timezone ?: 'UTC';

        return $this->ends_at->copy()->setTimezone($tz)->format('Y-m-d\TH:i');
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
        return $this->media()->wherePivot('collection', 'event-cover');
    }

    public function documents(): MorphToMany
    {
        return $this->media()->wherePivot('collection', 'event-document');
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

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeSearch($query, ?string $q)
    {
        return $q ? $query->where('title', 'like', '%'.$q.'%') : $query;
    }

    public function scopeUpcoming($query)
    {
        return $query->where('starts_at', '>=', now())->orderBy('starts_at');
    }

    public function settings(): HasOne
    {
        return $this->hasOne(EventSettings::class, 'event_id');
    }

    public function organizer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_user')->withTimestamps();
    }

    public function getShareUrl(): string
    {
        return url('/events/'.$this->slug);
    }
}
