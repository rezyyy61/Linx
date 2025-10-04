<?php

namespace App\Models\Campaign;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VolunteerSignup extends Model
{
    use HasFactory;

    protected $table = 'campaign_volunteer_signups';

    protected $fillable = [
        'campaign_id',
        'user_id',
        'name',
        'email',
        'role',
        'status',
        'availability',
        'meta',
        'signed_up_at',
    ];

    protected $casts = [
        'signed_up_at' => 'datetime',
        'availability' => 'array',
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
