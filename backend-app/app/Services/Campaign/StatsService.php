<?php

declare(strict_types=1);

namespace App\Services\Campaign;

use App\Models\Campaign\Campaign;
use Illuminate\Support\Facades\DB;

class StatsService
{
    public function forCampaign(Campaign $campaign, ?string $from = null, ?string $to = null): array
    {
        $content = $campaign->contents()
            ->when($from, fn($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn($q) => $q->where('created_at', '<=', $to));

        $supporters = $campaign->supporters()
            ->when($from, fn($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn($q) => $q->where('created_at', '<=', $to));

        $donations = $campaign->donations()
            ->when($from, fn($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn($q) => $q->where('created_at', '<=', $to));

        $totalDonations = (clone $donations)->sum('amount');
        $countDonations = (clone $donations)->count();

        return [
            'contents' => [
                'total' => (clone $content)->count(),
                'published' => (clone $content)->whereNotNull('published_at')->count(),
                'scheduled' => (clone $content)->where('status', 'scheduled')->count(),
            ],
            'supporters' => [
                'total' => (clone $supporters)->count(),
                'followers' => (clone $supporters)->where('role', 'follower')->count(),
                'volunteers' => (clone $supporters)->where('role', 'volunteer')->count(),
                'donors' => (clone $supporters)->where('role', 'donor')->count(),
            ],
            'donations' => [
                'count' => $countDonations,
                'total' => $totalDonations,
                'avg' => $countDonations > 0 ? (float) ($totalDonations / $countDonations) : 0.0,
            ],
        ];
    }
}
