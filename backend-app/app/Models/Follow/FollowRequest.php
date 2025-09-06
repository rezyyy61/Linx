<?php

namespace App\Models\Follow;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read User $actor
 * @property-read User $target
 */
class FollowRequest extends Model
{
    protected $fillable = [
        'actor_id',
        'target_id',
        'status',
        'decided_at',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            $a = (int) $m->actor_id;
            $t = (int) $m->target_id;
            $m->pair_low = min($a, $t);
            $m->pair_high = max($a, $t);
            $m->pending_flag = $m->status === 'pending' ? 1 : null;
        });

        static::updating(function (self $m) {
            $a = (int) $m->actor_id;
            $t = (int) $m->target_id;
            $m->pair_low = min($a, $t);
            $m->pair_high = max($a, $t);
            $m->pending_flag = $m->status === 'pending' ? 1 : null;
        });
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_id');
    }

    public function scopePending($q)
    {
        return $q->where('status', 'pending');
    }

    public function scopeBetween($q, int $a, int $b)
    {
        $low = min($a, $b);
        $high = max($a, $b);

        return $q->where('pair_low', $low)->where('pair_high', $high);
    }
}
