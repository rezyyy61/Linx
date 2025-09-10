<?php

namespace App\Models\Member;

use App\Enums\MembershipStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read User|null $owner
 * @property-read User|null $member
 * @property-read \Illuminate\Database\Eloquent\Collection<int,\App\Models\Member\MemberContentTarget> $targets
 */
class Membership extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id', 'member_id', 'email', 'contact_info', 'meta', 'status', 'consent_at', 'rejected_reason',
    ];

    protected $casts = [
        'contact_info' => 'array',
        'meta' => 'array',
        'consent_at' => 'datetime',
        'status' => MembershipStatus::class,
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    public function targets(): HasMany
    {
        return $this->hasMany(MemberContentTarget::class, 'membership_id');
    }

    public function scopeForOwner($q, int $ownerId)
    {
        return $q->where('owner_id', $ownerId);
    }

    protected function emailLower(): Attribute
    {
        return Attribute::make(
            set: fn ($v) => is_string($v) ? mb_strtolower(trim($v)) : $v
        );
    }
}
