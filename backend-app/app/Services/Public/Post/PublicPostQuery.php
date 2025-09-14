<?php

declare(strict_types=1);

namespace App\Services\Public\Post;

use App\Models\Post\Post as PostModel;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PublicPostQuery
{
    public function index(Request $request): CursorPaginator
    {
        $perPage = min((int) $request->integer('per_page', 20), 50);
        $sortDir = $request->get('sort') === 'oldest' ? 'asc' : 'desc';

        $q = $this->baseQuery();

        if ($request->filled('user_id')) {
            $q->where('user_id', (int) $request->get('user_id'));
        }

        if ($search = trim((string) $request->get('q', ''))) {
            $q->where(fn ($x) => $x->where('content', 'like', "%{$search}%"));
        }

        $from = $request->get('date_from');
        $to = $request->get('date_to');
        if ($from && $to && $from > $to) {
            [$from, $to] = [$to, $from];
        }
        if ($from) {
            $q->whereRaw('DATE(COALESCE(published_at, created_at)) >= ?', [$from]);
        }
        if ($to) {
            $q->whereRaw('DATE(COALESCE(published_at, created_at)) <= ?', [$to]);
        }

        $q->orderByRaw('COALESCE(published_at, created_at) '.$sortDir)
            ->orderBy('id', $sortDir);

        return $q->cursorPaginate($perPage)->withQueryString();
    }

    public function findOrFail(int $id): PostModel
    {
        return $this->baseQuery()->findOrFail($id);
    }

    /**
     * @return Builder<PostModel>
     */
    private function baseQuery(): Builder
    {
        return PostModel::query()
            ->published()
            ->publicVisible()
            ->with([
                'user',
                'user.profile',
                'user.profile.translations',
                'user.profile.logo',
                'user.profile.media',
                'media' => fn ($m) => $m
                    ->wherePivot('collection', PostModel::MEDIA_COLLECTION)
                    ->orderBy('mediables.order_column'),
            ]);
    }
}
