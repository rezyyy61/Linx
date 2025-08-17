<?php

namespace App\Models;

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string|null $disk
 * @property string $key
 * @property \App\Enums\MediaType|null $type
 * @property \App\Enums\MediaStatus|null $status
 * @property string|null $ext
 * @property int|null $size
 * @property string|null $mime
 * @property \Illuminate\Support\Carbon|null $created_at
 *
 * @property int|null $duration
 * @property int|null $width
 * @property int|null $height
 * @property array|null $meta
 * @property array|null $processed
 */
class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'disk',
        'key',
        'type',
        'status',
        'size',
        'mime',
        'ext',
        'width',
        'height',
        'duration',
        'sha256',
        'meta',
        'processed',
    ];

    protected $casts = [
        'meta' => 'array',
        'processed' => 'array',
        'type' => MediaType::class,
        'status' => MediaStatus::class,
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'duration' => 'float',
    ];

    public function isImage(): bool
    {
        return $this->type === MediaType::IMAGE;
    }

    public function isVideo(): bool
    {
        return $this->type === MediaType::VIDEO;
    }

    public function isAudio(): bool
    {
        return $this->type === MediaType::AUDIO;
    }

    public function isDocument(): bool
    {
        return $this->type === MediaType::DOCUMENT;
    }

    public function publicUrl(): ?string
    {
        $disk = Storage::disk($this->disk ?: 's3');

        if (method_exists($disk->getAdapter(), 'getClient')) {
            return $disk->url($this->key);
        }

        return null;
    }

    public function mediables(): \Illuminate\Database\Eloquent\Relations\MorphToMany|\Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->morphedByMany(
            related: Model::class,
            name: 'mediable',
            table: 'mediables'
        )->withPivot('collection', 'order_column')->withTimestamps();
    }
}
