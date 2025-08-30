<?php

namespace App\Models\Concerns;

use App\Models\Media;
use App\Services\Media\MediaService;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

trait HasMedia
{
    public static function bootHasMedia()
    {
        static::deleting(function ($model) {
            $usesSoftDeletes = in_array(SoftDeletes::class, class_uses_recursive($model));
            if ($usesSoftDeletes && method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }
            self::detachAndCleanupMedia($model);
        });

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class))) {
            static::registerModelEvent('forceDeleted', function ($model) {
                self::detachAndCleanupMedia($model);
            });
        }
    }

    protected static function detachAndCleanupMedia($model): void
    {
        $svc = app(MediaService::class);
        $ids = $model->media()->pluck('media.id')->all();

        if (! empty($ids)) {
            $model->media()->detach($ids);

            DB::afterCommit(function () use ($svc, $ids) {
                $medias = Media::whereIn('id', $ids)->get();
                foreach ($medias as $m) {
                    $svc->deleteIfOrphan($m);
                }
            });
        }
    }

    public function media(): MorphToMany
    {
        return $this->morphToMany(
            Media::class,
            'mediable',
            table: 'mediables',
            foreignPivotKey: 'mediable_id',
            relatedPivotKey: 'media_id'
        )->withPivot('collection', 'order_column')->withTimestamps();
    }
}
