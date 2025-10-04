<?php

namespace App\Models\Campaign;

use App\Contracts\Mediable;
use App\Contracts\PostableResourceable;
use App\Http\Resources\PublicApi\PublicMiniUserResource;
use App\Models\Media;
use App\Models\Share\Contracts\Shareable as ShareableContract;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;

class Campaign extends Model implements Mediable, PostableResourceable, ShareableContract
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'title',
        'slug',
        'excerpt',
        'description',
        'kind',
        'status',
        'visibility',
        'starts_at',
        'ends_at',
        'publish_at',
        'meta',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'publish_at' => 'datetime',
        'meta' => 'array',
    ];

    protected $appends = [
        'cover_url',
    ];

    protected $with = [
        'owner',
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

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'campaign';
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

    public function petitionSignatures(): HasMany
    {
        return $this->hasMany(PetitionSignature::class);
    }

    public function volunteerSignups(): HasMany
    {
        return $this->hasMany(VolunteerSignup::class);
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
        if ($this->relationLoaded('covers')) {
            $m = $this->covers->first();

            return $m instanceof Media ? $m->publicUrl() : null;
        }

        $m = $this->covers()->first();

        return $m instanceof Media ? $m->publicUrl() : null;
    }

    public function getShareUrl(): string
    {
        return url('/campaigns/'.$this->slug);
    }

    public function toPostableResource(): array
    {
        $meta = (array) ($this->meta ?? []);

        return [
            'id' => (int) $this->getKey(),
            'slug' => (string) ($this->slug ?? ''),
            'title' => (string) ($this->title ?? ''),
            'excerpt' => $this->excerpt ?? null,
            'description' => $this->description ?? null,
            'cover_url' => $this->cover_url ?? null,
            'starts_at' => optional($this->starts_at)?->toIso8601String(),
            'ends_at' => optional($this->ends_at)?->toIso8601String(),
            'kind' => $this->kind ?? ($meta['kind'] ?? null),
            'status' => $this->status ?? ($meta['status'] ?? null),
            'visibility' => $this->visibility ?? null,
            'publish_at' => optional($this->publish_at)?->toIso8601String(),
            'owner' => $this->owner
                ? (new PublicMiniUserResource($this->owner))->toArray(request())
                : null,

            'goal_amount' => $this->goal_amount ?? ($meta['goal_amount'] ?? null),
            'raised_amount' => $this->raised_amount ?? ($meta['raised_amount'] ?? null),
            'goal_currency' => $this->goal_currency ?? ($meta['goal_currency'] ?? null),

            'signature_goal' => $this->signature_goal ?? ($meta['signature_goal'] ?? null),
            'signatures_count' => $this->signatures_count ?? ($meta['signatures_count'] ?? null),

            'needed_slots' => $this->needed_slots ?? ($meta['needed_slots'] ?? null),
            'filled_slots' => $this->filled_slots ?? ($meta['filled_slots'] ?? null),

            'target_reach' => $this->target_reach ?? ($meta['target_reach'] ?? null),
            'current_reach' => $this->current_reach ?? ($meta['current_reach'] ?? null),
        ];
    }

    public function getPostableAlias(): string
    {
        return 'campaign';
    }

    public function getPostableSlug(): ?string
    {
        return $this->slug;
    }
}
