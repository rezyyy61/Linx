<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UserSearchRequest;
use App\Http\Resources\Profile\ProfileLiteResource;
use App\Models\Profile\Profile;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Schema;

class UserSearchController extends Controller
{
    public function index(UserSearchRequest $request): AnonymousResourceCollection
    {
        $v = $request->validated();
        $q = trim($v['q']);
        $perPage = (int) ($v['per_page'] ?? 15);
        $meId = optional($request->user())->id;

        $userNameCol = 'name';
        $transCol = null;
        if (Schema::hasColumn('profile_translations', 'name')) {
            $transCol = 'name';
        } elseif (Schema::hasColumn('profile_translations', 'title')) {
            $transCol = 'title';
        }

        $query = Profile::query()
            ->with([
                'logo' => fn ($q) => $q->limit(1),
                'translations',
                'user',
            ])
            ->when($meId, fn ($qq) => $qq->where('user_id', '<>', $meId))
            ->where(function ($qq) use ($q, $userNameCol, $transCol) {
                $qq->where('slug', 'like', "%{$q}%");

                if ($transCol) {
                    $qq->orWhereHas('translations', function ($t) use ($q, $transCol) {
                        $table = $t->getModel()->getTable(); // profile_translations
                        $t->where("{$table}.{$transCol}", 'like', "%{$q}%");
                    });
                }

                $qq->orWhereHas('user', function ($u) use ($q, $userNameCol) {
                    $table = $u->getModel()->getTable(); // users
                    $u->where("{$table}.{$userNameCol}", 'like', "%{$q}%");
                });
            })
            ->orderByRaw(
                'CASE
                    WHEN slug LIKE ? THEN 0
                    WHEN slug LIKE ? THEN 1
                    ELSE 2
                 END, slug ASC',
                ["{$q}%", "%{$q}%"]
            );

        $paginator = $query->paginate($perPage);

        return ProfileLiteResource::collection($paginator);
    }
}
