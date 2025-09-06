<?php

// database/factories/UserFactory.php

namespace Database\Factories;

use App\Models\User;
use App\Services\Profile\ProfileBootstrapService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->name();

        return [
            'name' => $name,
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            // Casts on the model will hash this automatically
            'password' => 'password',
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            // Keep it consistent with your RegisterService path
            if (class_exists(ProfileBootstrapService::class)) {
                App::make(ProfileBootstrapService::class)->createForUser($user);
            } else {
                // Fallback to factory if service isn't bound/available
                \App\Models\Profile\Profile::factory()->for($user)->create();
            }
        });
    }
}
