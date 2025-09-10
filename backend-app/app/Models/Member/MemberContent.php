<?php

namespace App\Models\Member;

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MemberContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id', 'type', 'title', 'body', 'options', 'status', 'scheduled_at', 'sent_at',
    ];

    protected $casts = [
        'options' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'type' => ContentType::class,
        'status' => ContentStatus::class,
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function targets()
    {
        return $this->hasMany(MemberContentTarget::class, 'content_id');
    }

    public function scopeForOwner($q, int $ownerId)
    {
        return $q->where('owner_id', $ownerId);
    }
}
