<?php

namespace App\Services\Member;

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Models\Member\MemberContent;
use App\Models\User;
use Illuminate\Support\Collection;

class ContentService
{
    public function create(User $owner, array $data): MemberContent
    {
        return MemberContent::create([
            'owner_id' => $owner->id,
            'type' => $data['type'] ?? ContentType::NEWSLETTER,
            'title' => $data['title'] ?? null,
            'body' => $data['body'] ?? null,
            'options' => $data['options'] ?? null,
            'status' => ContentStatus::DRAFT,
        ]);
    }

    public function schedule(MemberContent $content, \DateTimeInterface $when): MemberContent
    {
        $content->update([
            'status' => ContentStatus::SCHEDULED,
            'scheduled_at' => $when,
        ]);

        return $content;
    }

    public function markAsSent(MemberContent $content): MemberContent
    {
        $content->update([
            'status' => ContentStatus::SENT,
            'sent_at' => now(),
        ]);

        return $content;
    }

    public function listForOwner(User $owner, ?ContentType $type = null): Collection
    {
        $q = MemberContent::forOwner($owner->id);
        if ($type) {
            $q->where('type', $type);
        }

        return $q->get();
    }
}
