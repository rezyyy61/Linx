<?php

namespace App\Models\Campaign;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PetitionSignature extends Model
{
    use HasFactory;

    protected $table = 'campaign_petition_signatures';

    protected $fillable = [
        'campaign_id',
        'user_id',
        'name',
        'email',
        'status',
        'token',
        'meta',
        'signed_at',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
