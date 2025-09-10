<?php

namespace App\Models\Member;

use App\Enums\TargetStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read \App\Models\Member\MemberContent|null $content
 * @property-read \App\Models\Member\Membership|null $membership
 */
class MemberContentTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'content_id', 'membership_id', 'channel', 'status',
        'sent_at', 'opened_at', 'clicked_at', 'error', 'delivery_meta',
    ];

    protected $casts = [
        'delivery_meta' => 'array',
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'status' => TargetStatus::class,
    ];

    public function content(): BelongsTo
    {
        return $this->belongsTo(MemberContent::class, 'content_id');
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class, 'membership_id');
    }

    public function scopePending($q)
    {
        return $q->where('status', TargetStatus::PENDING);
    }
}
