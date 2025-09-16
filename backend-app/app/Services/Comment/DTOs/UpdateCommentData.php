<?php

namespace App\Services\Comment\DTOs;

class UpdateCommentData
{
    public function __construct(
        public int $id,
        public string $body
    ) {}

    public static function make(int $id, string $body): self
    {
        return new self($id, $body);
    }
}
