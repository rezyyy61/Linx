<?php

declare(strict_types=1);

namespace App\Contracts;

interface PostableResourceable
{
    public function toPostableResource(): array;

    public function getPostableAlias(): string;

    public function getPostableSlug(): ?string;
}
