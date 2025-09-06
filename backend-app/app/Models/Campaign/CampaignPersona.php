<?php

declare(strict_types=1);

namespace App\Models\Campaign;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignPersona extends Model
{
    use HasFactory;

    protected $table = 'campaign_personas';

    protected $fillable = [
        'campaign_id',
        'name',
        'notes',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
