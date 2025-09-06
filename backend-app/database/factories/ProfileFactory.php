<?php

namespace Database\Factories\Profile;

use App\Models\Profile\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Profile> */
class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    public function definition(): array
    {
        $name = $this->faker->name();
        $slug = Str::slug($name).'-'.$this->faker->unique()->numberBetween(1000, 9999);

        return [
            'slug' => $slug,
            'entity_type' => 'person',
            'location' => $this->faker->city(),
            'founded_year' => null,
            'status' => 'active',
            'verified' => $this->faker->boolean(20),
            'published_at' => $this->faker->optional(0.7)->dateTimeBetween('-2 years', 'now'),
            'avatar_color' => $this->faker->hexColor(),
            'user_id' => User::factory(),
        ];
    }
}
