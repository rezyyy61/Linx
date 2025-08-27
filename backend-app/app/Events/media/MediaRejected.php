<?php

namespace App\Events\media;

class MediaRejected
{
    public function __construct(
        public int $mediaId,
        public string $key,
        public string $reason
    ) {}
}
