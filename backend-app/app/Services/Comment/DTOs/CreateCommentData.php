<?php

namespace App\Services\Comment\DTOs;

class CreateCommentData
{
    public function __construct(
        public string $commentableType,
        public int|string $commentableId,
        public ?int $parentId,
        public string $body
    ) {}

    public static function make(string $commentableType, int|string $commentableId, ?int $parentId, string $body): self
    {
        return new self($commentableType, $commentableId, $parentId, $body);
    }
}
