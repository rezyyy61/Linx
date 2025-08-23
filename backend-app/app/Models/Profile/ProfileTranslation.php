<?php

namespace App\Models\Profile;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileTranslation extends Model
{
    protected $fillable = [
        'profile_id',
        'locale',
        'tagline',
        'about',
        'goals',
        'activities',
        'structure',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
