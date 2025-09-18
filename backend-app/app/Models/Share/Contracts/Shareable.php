<?php

declare(strict_types=1);

namespace App\Models\Share\Contracts;

interface Shareable
{
    public function getShareUrl(): string;
}
