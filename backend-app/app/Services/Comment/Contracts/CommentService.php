<?php

namespace App\Services\Comment\Contracts;

use App\Models\Comment\Comment as CommentModel;
use App\Models\User;
use App\Services\Comment\DTOs\CreateCommentData;
use App\Services\Comment\DTOs\UpdateCommentData;
use Illuminate\Contracts\Pagination\CursorPaginator;

interface CommentService
{
    public function listRoots(string $commentableType, int|string $commentableId, ?string $cursor = null, int $perPage = 20, string $sort = 'new', bool $includeHidden = false): CursorPaginator;

    public function listChildren(int $parentId, ?string $cursor = null, int $perPage = 20, string $sort = 'new', bool $includeHidden = false): CursorPaginator;

    public function create(CreateCommentData $data, User $actor): CommentModel;

    public function update(UpdateCommentData $data, User $actor, bool $force = false): CommentModel;

    public function delete(int $id, User $actor, bool $force = false): void;

    public function toggleLike(int $commentId, User $actor): array;

    public function likers(int $commentId, ?string $cursor = null, int $perPage = 20): CursorPaginator;
}
