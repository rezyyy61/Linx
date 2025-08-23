<?php

declare(strict_types=1);

namespace Tests\Feature\Post;

use App\Models\Post\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PostApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutExceptionHandling();
    }

    private function assertStatusAndDump(TestResponse $response, int $expected): void
    {
        if ($response->status() !== $expected) {
            $response->dump();
        }
        $response->assertStatus($expected);
    }

    public function test_user_cannot_update_others_post(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($user, 'web');

        $post = Post::factory()->create(['user_id' => $other->id, 'content' => 'x']);

        $res = $this->patchJson("/api/posts/{$post->id}", ['content' => 'y']);
        $this->assertStatusAndDump($res, 403);
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'content' => 'x']);
    }

    public function test_index_returns_paginated_list(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'web');

        Post::factory()->count(3)->create(['user_id' => $user->id]);

        $res = $this->getJson('/api/posts');
        $this->assertStatusAndDump($res, 200);
        $res->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_show_returns_single_post(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'web');

        $post = Post::factory()->create(['user_id' => $user->id, 'content' => 'z']);

        $res = $this->getJson("/api/posts/{$post->id}");
        $this->assertStatusAndDump($res, 200);
        $res->assertJsonPath('data.id', $post->id);
        $res->assertJsonPath('data.content', 'z');
    }
}
