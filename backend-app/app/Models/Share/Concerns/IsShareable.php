<?php

declare(strict_types=1);

namespace App\Models\Share\Concerns;

use App\Models\Share\Share;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait IsShareable
{
    public function shares(): MorphMany
    {
        return $this->morphMany(Share::class, 'shareable');
    }
}
