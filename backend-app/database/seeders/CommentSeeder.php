<?php

namespace Database\Seeders;

use App\Models\Comment\Comment;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    public function run(): void
    {
        $commentables = User::factory()->count(3)->create();
        $authors = User::factory()->count(5)->create();

        foreach ($commentables as $target) {
            for ($i = 0; $i < 5; $i++) {
                $root = Comment::factory()
                    ->for($target, 'commentable')
                    ->by($authors->random())
                    ->create();

                $level1 = Comment::factory()
                    ->count(random_int(1, 3))
                    ->for($target, 'commentable')
                    ->by($authors->random())
                    ->state(fn () => ['parent_id' => $root->id])
                    ->create();

                foreach ($level1 as $child) {
                    Comment::factory()
                        ->count(random_int(0, 2))
                        ->for($target, 'commentable')
                        ->by($authors->random())
                        ->state(fn () => ['parent_id' => $child->id])
                        ->create();
                }
            }
        }
    }
}
