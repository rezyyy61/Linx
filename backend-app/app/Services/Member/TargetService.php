<?php

namespace App\Services\Member;

use App\Enums\TargetStatus;
use App\Models\Member\MemberContent;
use App\Models\Member\MemberContentTarget;
use Illuminate\Support\Collection;

class TargetService
{
    public function createTargets(MemberContent $content, Collection $memberships, string $channel = 'email'): Collection
    {
        $targets = collect();
        foreach ($memberships as $m) {
            $targets->push(MemberContentTarget::create([
                'content_id' => $content->id,
                'membership_id' => $m->id,
                'channel' => $channel,
                'status' => TargetStatus::PENDING,
            ]));
        }

        return $targets;
    }

    public function markSent(MemberContentTarget $target): MemberContentTarget
    {
        $target->update([
            'status' => TargetStatus::SENT,
            'sent_at' => now(),
        ]);

        return $target;
    }

    public function markFailed(MemberContentTarget $target, string $error): MemberContentTarget
    {
        $target->update([
            'status' => TargetStatus::FAILED,
            'error' => $error,
        ]);

        return $target;
    }

    public function markOpened(MemberContentTarget $target): MemberContentTarget
    {
        $target->update([
            'status' => TargetStatus::OPENED,
            'opened_at' => now(),
        ]);

        return $target;
    }

    public function markClicked(MemberContentTarget $target): MemberContentTarget
    {
        $target->update([
            'status' => TargetStatus::CLICKED,
            'clicked_at' => now(),
        ]);

        return $target;
    }

    public function pendingForContent(MemberContent $content): Collection
    {
        return $content->targets()->where('status', TargetStatus::PENDING)->get();
    }
}
