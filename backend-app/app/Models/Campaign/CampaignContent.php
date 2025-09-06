<?php

declare(strict_types=1);

namespace App\Models\Campaign;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignContent extends Model
{
    use HasFactory;

    protected $table = 'campaign_contents';

    protected $fillable = [
        'campaign_id',
        'type',
        'channel',
        'title',
        'body',
        'schedule_at',
        'published_at',
        'status',
        'metrics_json',
    ];

    protected $casts = [
        'schedule_at' => 'datetime',
        'published_at' => 'datetime',
        'metrics_json' => 'array',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
