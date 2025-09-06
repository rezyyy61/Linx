<?php

declare(strict_types=1);

namespace App\Models\Campaign;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignSupporter extends Model
{
    use HasFactory;

    protected $table = 'campaign_supporters';

    protected $fillable = [
        'campaign_id',
        'user_id',
        'role',
        'contact_email',
        'contact_phone',
        'tags_json',
        'consent_at',
    ];

    protected $casts = [
        'tags_json' => 'array',
        'consent_at' => 'datetime',
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
