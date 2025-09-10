<?php

namespace Database\Factories;

use App\Models\Follow\Follow;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Follow> */
class FollowFactory extends Factory
{
    protected $model = Follow::class;

    public function definition(): array
    {
        return [
            'follower_id' => User::factory(),
            'followed_id' => User::factory(),
        ];
    }

    public function between(User $follower, User $followed): static
    {
        return $this->state([
            'follower_id' => $follower->id,
            'followed_id' => $followed->id,
        ]);
    }
}
