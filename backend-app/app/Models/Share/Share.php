<?php

declare(strict_types=1);

namespace App\Models\Share;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Share extends Model
{
    protected $table = 'shares';

    protected $fillable = [
        'shareable_type',
        'shareable_id',
        'creator_id',
        'channel',
        'short_code',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'bool',
    ];

    public function shareable(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(ShareClick::class, 'share_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }
}
