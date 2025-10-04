<?php

namespace App\Services\Campaign;

use Illuminate\Database\Eloquent\Builder;

class CampaignQuery
{
    public static function apply(Builder $q, array $filters): Builder
    {
        if (! empty($filters['q'])) {
            $s = $filters['q'];
            $q->where(function (Builder $qq) use ($s) {
                $qq->where('title', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%");
            });
        }

        if (! empty($filters['status'])) {
            $q->where('status', $filters['status']);
        }

        if (! empty($filters['kind'])) {
            $q->where('kind', $filters['kind']);
        }

        if (! empty($filters['visibility'])) {
            $q->where('visibility', $filters['visibility']);
        }

        if (! empty($filters['owner_id'])) {
            $q->where('owner_id', (int) $filters['owner_id']);
        }

        if (! empty($filters['starts_at_from'])) {
            $q->where('starts_at', '>=', $filters['starts_at_from']);
        }

        if (! empty($filters['ends_at_to'])) {
            $q->where('ends_at', '<=', $filters['ends_at_to']);
        }

        return $q;
    }
}
