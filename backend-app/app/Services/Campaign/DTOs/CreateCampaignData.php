<?php

namespace App\Services\Campaign\DTOs;

class CreateCampaignData
{
    public int $owner_id;

    public string $title;

    public ?string $excerpt;

    public ?string $description;

    public string $kind;

    public string $status;

    public string $visibility;

    public ?string $starts_at;

    public ?string $ends_at;

    public ?string $publish_at;

    public ?string $slug;

    public ?int $cover_id;

    /** @var array<int, array{id:int,order?:int}>|null */
    public ?array $documents;

    /** @var array<string,mixed> */
    public array $meta;

    public function __construct(
        int $owner_id,
        string $title,
        string $kind,
        string $visibility = 'public',
        string $status = 'draft',
        ?string $excerpt = null,
        ?string $description = null,
        ?string $starts_at = null,
        ?string $ends_at = null,
        ?string $publish_at = null,
        ?string $slug = null,
        ?int $cover_id = null,
        ?array $documents = null,
        array $meta = []
    ) {
        $this->owner_id = $owner_id;
        $this->title = $title;
        $this->kind = $kind;
        $this->visibility = $visibility;
        $this->status = $status;
        $this->excerpt = $excerpt;
        $this->description = $description;
        $this->starts_at = $starts_at;
        $this->ends_at = $ends_at;
        $this->publish_at = $publish_at;
        $this->slug = $slug;
        $this->cover_id = $cover_id;
        $this->documents = $documents;
        $this->meta = $meta;
    }

    public function toArray(): array
    {
        return [
            'owner_id' => $this->owner_id,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'description' => $this->description,
            'kind' => $this->kind,
            'status' => $this->status,
            'visibility' => $this->visibility,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'publish_at' => $this->publish_at,
            'slug' => $this->slug,
            'cover_id' => $this->cover_id,
            'documents' => $this->documents,
            'meta' => $this->meta,
        ];
    }
}
