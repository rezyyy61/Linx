<?php

namespace App\Models\Concerns;

use App\Models\Media;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasMedia
{
    public function media(): MorphToMany
    {
        return $this->morphToMany(
            Media::class,
            'mediable',
            table: 'mediables',
            foreignPivotKey: 'mediable_id',
            relatedPivotKey: 'media_id'
        )
            ->withPivot('collection', 'order_column')
            ->withTimestamps();
    }
}
