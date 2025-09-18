<?php

declare(strict_types=1);

namespace App\Services\Share;

use Illuminate\Support\Str;

class ShareCodeGenerator
{
    public function generate(int $length = 10): string
    {
        return Str::random($length);
    }
}
