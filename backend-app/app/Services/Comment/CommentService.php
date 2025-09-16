<?php

namespace App\Services\Comment;

use App\Events\Comment\CommentCreated;
use App\Events\Comment\CommentDeleted;
use App\Events\Comment\CommentLikeToggled;
use App\Events\Comment\CommentUpdated;
use App\Models\Comment\Comment as CommentModel;
use App\Models\Comment\CommentLike;
use App\Models\Post\Post;
use App\Models\User;
use App\Notifications\Comment\Mentioned;
use App\Notifications\Comment\OnPost;
use App\Services\Comment\Contracts\CommentService as CommentServiceContract;
use App\Services\Comment\DTOs\CreateCommentData;
use App\Services\Comment\DTOs\UpdateCommentData;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class CommentService implements CommentServiceContract
{
    private int $editWindowMinutes = 5;

    public function listRoots(string $commentableType, int|string $commentableId, ?string $cursor = null, int $perPage = 20, string $sort = 'new', bool $includeHidden = false): CursorPaginator
    {
        $authId = auth()->id();

        $q = CommentModel::query()
            ->with(['user.profile.translations', 'user.profile.logo'])
            ->withCount(['likes as liked_by_me' => fn ($qq) => $qq->where('user_id', $authId)])
            ->where('commentable_type', $commentableType)
            ->where('commentable_id', $commentableId)
            ->whereNull('parent_id');

        if (! $includeHidden) {
            $q->where('status', 'visible');
        }

        if ($sort === 'top') {
            $q->orderByDesc('replies_count')->orderByDesc('id');
        } else {
            $q->orderByDesc('id');
        }

        return $q->cursorPaginate($perPage, ['*'], 'cursor', $cursor);
    }

    public function listChildren(int $parentId, ?string $cursor = null, int $perPage = 20, string $sort = 'new', bool $includeHidden = false): CursorPaginator
    {
        $authId = auth()->id();

        $parent = CommentModel::query()->findOrFail($parentId);

        $q = CommentModel::query()
            ->with(['user.profile.translations', 'user.profile.logo'])
            ->withCount(['likes as liked_by_me' => fn ($qq) => $qq->where('user_id', $authId)])
            ->where('parent_id', $parent->id);

        if (! $includeHidden) {
            $q->where('status', 'visible');
        }

        if ($sort === 'top') {
            $q->orderByDesc('replies_count')->orderByDesc('id');
        } else {
            $q->orderByDesc('id');
        }

        return $q->cursorPaginate($perPage, ['*'], 'cursor', $cursor);
    }

    public function create(CreateCommentData $data, User $actor): CommentModel
    {
        return DB::transaction(function () use ($data, $actor) {
            $comment = CommentModel::query()->create([
                'commentable_type' => $data->commentableType,
                'commentable_id' => $data->commentableId,
                'parent_id' => $data->parentId,
                'user_id' => $actor->id,
                'body' => $data->body,
                'status' => 'visible',
            ]);

            $comment->load(['user.profile.translations', 'user.profile.logo'])
                ->loadCount(['likes as liked_by_me' => fn ($qq) => $qq->where('user_id', $actor->id)]);

            event(new CommentCreated($comment));

            if ($comment->commentable_type === Post::class) {
                $post = Post::query()
                    ->with(['user.profile.translations', 'user.profile.logo'])
                    ->select('id', 'user_id')
                    ->find($comment->commentable_id);

                if ($post && (int) $post->user_id !== (int) $actor->id && $post->user) {
                    Notification::send($post->user, new OnPost(
                        $this->actorLite($actor),
                        (int) $post->id,
                        (int) $comment->id,
                        $this->excerpt($comment->body)
                    ));
                }

                $userSlugs = $this->extractMentionSlugs($comment->body);
                if (! empty($userSlugs)) {
                    $skipIds = [(int) $actor->id];
                    if ($post) {
                        $skipIds[] = (int) $post->user_id;
                    }

                    $mentioned = User::query()
                        ->select('users.*')
                        ->join('profiles', 'profiles.user_id', '=', 'users.id')
                        ->whereIn('profiles.slug', $userSlugs)
                        ->whereNotIn('users.id', $skipIds)
                        ->get();

                    foreach ($mentioned as $u) {
                        Notification::send($u, new Mentioned(
                            $this->actorLite($actor),
                            $post ? (int) $post->id : (int) $comment->commentable_id,
                            (int) $comment->id,
                            $this->excerpt($comment->body)
                        ));
                    }
                }
            }

            return $comment;
        });
    }

    public function update(UpdateCommentData $data, User $actor, bool $force = false): CommentModel
    {
        $comment = CommentModel::query()->findOrFail($data->id);

        if (! $force) {
            if ($actor->id !== (int) $comment->user_id) {
                throw new AuthorizationException;
            }
            $limit = Carbon::parse($comment->created_at)->addMinutes($this->editWindowMinutes);
            if (now()->greaterThan($limit)) {
                throw new AuthorizationException;
            }
        }

        $comment->body = $data->body;
        $comment->save();

        $comment->load(['user.profile.translations', 'user.profile.logo'])
            ->loadCount(['likes as liked_by_me' => fn ($qq) => $qq->where('user_id', $actor->id)]);

        event(new CommentUpdated($comment));

        return $comment;
    }

    public function delete(int $id, User $actor, bool $force = false): void
    {
        $comment = CommentModel::query()->findOrFail($id);

        if (! $force && $actor->id !== (int) $comment->user_id) {
            throw new AuthorizationException;
        }

        $postId = (int) $comment->commentable_id;
        $commentId = (int) $comment->id;
        $parentId = $comment->parent_id ? (int) $comment->parent_id : null;
        $rootId = (int) ($comment->root_id ?: $comment->id);

        if ($force) {
            $comment->forceDelete();
            event(new CommentDeleted($postId, $commentId, $parentId, $rootId));

            return;
        }

        $comment->status = 'deleted_soft';
        $comment->save();
        $comment->delete();

        event(new CommentDeleted($postId, $commentId, $parentId, $rootId));
    }

    public function toggleLike(int $commentId, User $actor): array
    {
        return DB::transaction(function () use ($commentId, $actor) {
            $comment = CommentModel::query()->findOrFail($commentId);

            $existing = CommentLike::query()
                ->where('comment_id', $comment->id)
                ->where('user_id', $actor->id)
                ->first();

            if ($existing) {
                $existing->delete();
                $comment->decrement('reactions_count');

                $payload = ['liked' => false, 'count' => (int) $comment->reactions_count];

                event(new CommentLikeToggled(
                    (int) $comment->commentable_id,
                    (int) $comment->id,
                    false,
                    $payload['count']
                ));

                return $payload;
            }

            CommentLike::query()->create([
                'comment_id' => $comment->id,
                'user_id' => $actor->id,
            ]);

            $comment->increment('reactions_count');

            $payload = ['liked' => true, 'count' => (int) $comment->reactions_count];

            event(new CommentLikeToggled(
                (int) $comment->commentable_id,
                (int) $comment->id,
                true,
                $payload['count']
            ));

            return $payload;
        });
    }

    public function likers(int $commentId, ?string $cursor = null, int $perPage = 20): CursorPaginator
    {
        $q = User::query()
            ->with(['profile.translations', 'profile.logo'])
            ->select('users.*')
            ->join('comment_likes', 'comment_likes.user_id', '=', 'users.id')
            ->where('comment_likes.comment_id', $commentId)
            ->orderByDesc('comment_likes.id');

        return $q->cursorPaginate($perPage, ['*'], 'cursor', $cursor);
    }

    private function actorLite(User $u): array
    {
        $u->loadMissing(['profile.translations', 'profile.logo']);
        $profile = $u->profile;

        $translations = $profile ? ($profile->translations ?? collect()) : collect();
        $tr = $translations->firstWhere('locale', app()->getLocale()) ?: $translations->first();
        $name = ($tr->name ?? $tr->title ?? null) ?? ($profile->name ?? $u->name);

        $logoMedia = $profile
            ? ($profile->relationLoaded('logo') ? $profile->logo->first() : $profile->logo()->first())
            : null;

        $avatar = ($logoMedia && method_exists($logoMedia, 'publicUrl')) ? $logoMedia->publicUrl() : null;

        return [
            'id' => (int) $u->id,
            'slug' => $profile ? $profile->slug : null,
            'username' => $u->username ?? null,
            'avatar' => $avatar,
            'name' => $name,
        ];
    }

    private function excerpt(string $text, int $len = 140): string
    {
        $t = trim(preg_replace('/\s+/', ' ', strip_tags($text)));
        if (mb_strlen($t, 'UTF-8') <= $len) {
            return $t;
        }

        return mb_substr($t, 0, $len, 'UTF-8').'…';
    }

    private function extractMentionSlugs(string $text): array
    {
        preg_match_all('/@([A-Za-z0-9_.-]{2,64})/u', $text, $m);

        return array_values(array_unique(array_map('strval', $m[1])));
    }
}
