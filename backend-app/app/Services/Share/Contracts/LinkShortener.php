<?php

declare(strict_types=1);

namespace App\Services\Share\Contracts;

interface LinkShortener
{
    public function shorten(string $target, array $utm = []): string;
}
