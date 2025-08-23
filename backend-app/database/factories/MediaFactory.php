<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MediaStatus;
use App\Enums\MediaType;
use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement([MediaType::IMAGE, MediaType::VIDEO, MediaType::AUDIO, MediaType::DOCUMENT]);

        return [
            'disk' => config('filesystems.default', 'public'),
            'key' => 'tests/'.Str::uuid()->toString(),
            'type' => $type,
            'status' => MediaStatus::READY,
            'ext' => null,
            'size' => null,
            'mime' => null,
        ];
    }

    public function uploaded(): static
    {
        return $this->state(fn () => ['status' => MediaStatus::UPLOADED]);
    }

    public function processing(): static
    {
        return $this->state(fn () => ['status' => MediaStatus::PROCESSING]);
    }
}
