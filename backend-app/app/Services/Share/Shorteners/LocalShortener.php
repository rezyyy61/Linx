<?php

declare(strict_types=1);

namespace App\Services\Share\Shorteners;

use App\Services\Share\Contracts\LinkShortener;

class LocalShortener implements LinkShortener
{
    public function shorten(string $target, array $utm = []): string
    {
        return $target;
    }
}
