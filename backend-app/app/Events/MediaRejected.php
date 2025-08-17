<?php

namespace App\Events;

class MediaRejected
{
    public function __construct(
        public int $mediaId,
        public string $key,
        public string $reason
    ) {}
}
