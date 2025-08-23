<?php

declare(strict_types=1);

namespace Tests\Feature\Post;

use App\Models\Post\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PostCrudTest extends TestCase
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

    public function test_create_post_with_content_only_returns_201(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'web');

        $payload = [
            'content' => 'hello world',
            'visibility' => 'public',
            'status' => 'published',
        ];

        $res = $this->postJson('/api/posts', $payload);
        $this->assertStatusAndDump($res, 201);
        $res->assertJsonPath('data.content', 'hello world');
        $this->assertDatabaseHas('posts', ['user_id' => $user->id, 'content' => 'hello world']);
    }

    public function test_update_own_post_changes_content(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'web');

        $post = Post::factory()->create(['user_id' => $user->id, 'content' => 'a']);

        $res = $this->patchJson("/api/posts/{$post->id}", ['content' => 'b']);
        $this->assertStatusAndDump($res, 200);
        $res->assertJsonPath('data.content', 'b');
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'content' => 'b']);
    }

    public function test_delete_own_post_soft_deletes(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'web');

        $post = Post::factory()->create(['user_id' => $user->id]);

        $res = $this->deleteJson("/api/posts/{$post->id}");
        $this->assertStatusAndDump($res, 204);
        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }
}
