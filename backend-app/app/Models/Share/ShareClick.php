<?php

declare(strict_types=1);

namespace App\Models\Share;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShareClick extends Model
{
    protected $table = 'share_clicks';

    protected $fillable = [
        'share_id',
        'short_code',
        'ip_hash',
        'referer',
        'user_agent',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    public function share(): BelongsTo
    {
        return $this->belongsTo(Share::class, 'share_id');
    }
}
