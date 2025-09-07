<?php

namespace App\Models\Campaign;

use App\Contracts\Mediable;
use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;

class Campaign extends Model implements Mediable
{
    use HasFactory;

    protected $table = 'campaigns';

    protected $fillable = [
        'owner_id',
        'title',
        'goal',
        'description',
        'starts_at',
        'ends_at',
        'status',
        'donation_enabled',
        'slug',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'donation_enabled' => 'boolean',
        'goal' => 'float',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $m) {
            if (empty($m->slug)) {
                $m->slug = static::uniqueSlug($m->title ?: 'campaign');
            }
        });

        static::updating(function (self $m) {
            if (empty($m->slug) && ! empty($m->title)) {
                $m->slug = static::uniqueSlug($m->title, $m->id);
            }
        });
    }

    public static function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $base = Str::slug($base) ?: 'campaign';
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
        return $this->media()->wherePivot('collection', 'campaign-cover');
    }

    public function documents(): MorphToMany
    {
        return $this->media()->wherePivot('collection', 'campaign-document');
    }

    public function getCoverUrlAttribute(): ?string
    {
        $m = $this->relationLoaded('covers') ? $this->covers->first() : $this->covers()->first();

        return $m instanceof Media ? $m->publicUrl() : null;
    }

    public function donationIntents()
    {
    }
}
