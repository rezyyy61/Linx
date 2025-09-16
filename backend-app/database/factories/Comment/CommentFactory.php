<?php

namespace Database\Factories\Comment;

use App\Models\Comment\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'commentable_type' => User::class,
            'commentable_id' => User::factory(),
            'user_id' => User::factory(),
            'parent_id' => null,
            'root_id' => null,
            'depth' => 0,
            'path' => '',
            'body' => $this->faker->paragraph(),
            'status' => 'visible',
            'replies_count' => 0,
            'reactions_count' => 0,
        ];
    }

    public function forCommentable(Model $model): static
    {
        return $this->state(fn () => [
            'commentable_type' => get_class($model),
            'commentable_id' => $model->getKey(),
        ]);
    }

    public function by(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }

    public function asReplyTo(Comment $parent): static
    {
        return $this->state(fn () => ['parent_id' => $parent->id]);
    }
}
