<?php

namespace App\Http\Resources\Comment;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CommentCursorCollection extends ResourceCollection
{
    public $collects = CommentResource::class;

    public function toArray($request): array
    {
        return [
            'items' => $this->collection,
        ];
    }

    public function with($request): array
    {
        $p = $this->resource;
        $next = method_exists($p, 'nextCursor') && $p->nextCursor() ? $p->nextCursor()->encode() : null;
        $prev = method_exists($p, 'previousCursor') && $p->previousCursor() ? $p->previousCursor()->encode() : null;

        return [
            'cursor' => [
                'next' => $next,
                'prev' => $prev,
            ],
        ];
    }
}
