<?php

declare(strict_types=1);

namespace App\Models\Campaign;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Donation extends Model
{
    use HasFactory;

    protected $table = 'donations';

    protected $fillable = [
        'campaign_id',
        'supporter_id',
        'amount',
        'currency',
        'provider',
        'provider_ref',
        'status',
        'paid_at',
        'meta',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'meta' => 'array',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function supporter(): BelongsTo
    {
        return $this->belongsTo(CampaignSupporter::class, 'supporter_id');
    }
}
