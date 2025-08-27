<?php

namespace App\Services\Media;

use App\Contracts\Mediable;
use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Events\media\MediaUpdated;
use App\Jobs\ScanFileJob;
use App\Models\Media;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Mime\MimeTypes;

class MediaService
{
    public function generateKey(string $extension, string $prefix = 'originals'): string
    {
        return $prefix.'/'.Str::uuid().'.'.strtolower($extension);
    }

    protected function guessContentType(string $extension): string
    {
        $extension = strtolower($extension);
        $mimeTypes = new MimeTypes;
        $guessed = $mimeTypes->getMimeTypes($extension);

        return $guessed[0] ?? 'application/octet-stream';
    }

    public function createPresignedUploadUrl(
        string $extension,
        MediaType $type,
        int $expirySeconds = 3600
    ): array {
        $storageDisk = 's3';
        $signDisk = 's3_public';
        $key = $this->generateKey($extension);

        $media = Media::create([
            'disk' => $storageDisk,
            'key' => $key,
            'type' => $type->value,
            'status' => MediaStatus::CREATED,
            'ext' => $extension,
        ]);

        $cfgStore = config("filesystems.disks.$storageDisk");
        $cfgSign = config("filesystems.disks.$signDisk");
        $bucket = $cfgStore['bucket'] ?? null;
        if (! $bucket) {
            throw new \RuntimeException("S3 bucket is not configured for disk [$storageDisk].");
        }

        $client = new \Aws\S3\S3Client([
            'version' => 'latest',
            'region' => $cfgSign['region'] ?? 'us-east-1',
            'endpoint' => $cfgSign['endpoint'] ?? null,   // http://localhost:9000
            'use_path_style_endpoint' => $cfgSign['use_path_style_endpoint'] ?? true,
            'credentials' => [
                'key' => $cfgSign['key'] ?? null,
                'secret' => $cfgSign['secret'] ?? null,
            ],
        ]);

        $contentType = $this->guessContentType($extension);

        $cmd = $client->getCommand('PutObject', [
            'Bucket' => $bucket,
            'Key' => $key,
            'ContentType' => $contentType,
        ]);

        $request = $client->createPresignedRequest($cmd, '+'.$expirySeconds.' seconds');

        return [
            'id' => $media->id,
            'upload_url' => (string) $request->getUri(),
            'key' => $key,
            'contentType' => $contentType,
        ];
    }

    public function finalizeUpload(Media $media, array $extra = []): Media
    {
        $disk = Storage::disk($media->disk);

        if ($disk->exists($media->key)) {
            $media->size = $disk->size($media->key);
            $media->mime = $disk->mimeType($media->key);
        }

        $media->status = MediaStatus::UPLOADED;
        $media->fill($extra);
        $media->save();
        event(new MediaUpdated($media->id, ['status' => 'UPLOADED']));
        ScanFileJob::dispatch($media->id);

        return $media;
    }

    /***
     * @param Mediable $model
     * @param Media $media
     * @param string|null $collection
     * @param int|null $order
     */
    public function attachMedia(Mediable $model, Media $media, ?string $collection = 'default', ?int $order = 0): void
    {
        DB::transaction(function () use ($model, $media, $collection, $order) {
            $exists = $model->media()
                ->where('media.id', $media->id)
                ->wherePivot('collection', $collection)
                ->exists();

            if (! $exists) {
                $model->media()->attach($media->id, [
                    'collection' => $collection,
                    'order_column' => $order ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function detachMedia(Mediable $model, Media $media, ?string $collection = null): void
    {
        $relation = $model->media();

        if ($collection !== null) {
            $relation->wherePivot('collection', $collection)->detach($media->id);
        } else {
            $relation->detach($media->id);
        }
    }

    public function replaceSingle(Mediable $model, Media $media, string $collection = 'logo', int $order = 0): void
    {
        DB::transaction(function () use ($model, $media, $collection, $order) {
            /** @var MorphToMany $rel */
            $rel = $model->media();

            $rel->wherePivot('collection', $collection)->detach();

            $rel->attach($media->id, [
                'collection' => $collection,
                'order_column' => $order,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }
}
