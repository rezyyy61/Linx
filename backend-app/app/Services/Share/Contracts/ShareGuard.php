<?php

declare(strict_types=1);

namespace App\Services\Share\Contracts;

use App\Enums\Share\ShareChannel;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

interface ShareGuard
{
    public function canShare(Model $shareable, ?User $user, ShareChannel $channel): bool;
}
