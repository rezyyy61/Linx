<?php

declare(strict_types=1);

namespace Tests\Feature\Post;

use App\Models\Media;
use App\Models\Post\Post;
use App\Models\User;
use Database\Factories\MediaFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PostMediaTest extends TestCase
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

    public function test_create_post_with_ready_media(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'web');

        $m1 = Media::factory()->create();
        $m2 = Media::factory()->create();
        $payload = [
            'content' => 'p',
            'visibility' => 'public',
            'status' => 'published',
            'media' => [
                ['id' => $m1->id, 'order' => 1],
                ['id' => $m2->id, 'order' => 0],
            ],
        ];

        $res = $this->postJson('/api/posts', $payload);
        $this->assertStatusAndDump($res, 201);

        $postId = $res->json('data.id');
        $this->assertDatabaseHas('mediables', [
            'mediable_type' => Post::class,
            'mediable_id' => $postId,
            'media_id' => $m1->id,
            'collection' => 'post',
            'order_column' => 1,
        ]);
        $this->assertDatabaseHas('mediables', [
            'mediable_type' => Post::class,
            'mediable_id' => $postId,
            'media_id' => $m2->id,
            'collection' => 'post',
            'order_column' => 0,
        ]);

        $show = $this->getJson("/api/posts/{$postId}");
        $this->assertStatusAndDump($show, 200);
        $this->assertSame($m2->id, $show->json('data.media.0.id'));
        $this->assertSame($m1->id, $show->json('data.media.1.id'));
    }

    public function test_create_post_rejects_non_ready_media(): void
    {
        $this->withExceptionHandling();

        $user = User::factory()->create();
        $this->actingAs($user, 'web');

        $mReady = Media::factory()->create();
        $mNotReady = (new MediaFactory)->uploaded()->create();

        $payload = [
            'content' => 'x',
            'media' => [
                ['id' => $mReady->id, 'order' => 0],
                ['id' => $mNotReady->id, 'order' => 1],
            ],
        ];

        $res = $this->postJson('/api/posts', $payload);
        $this->assertStatusAndDump($res, 422);
    }

    public function test_update_post_syncs_media_and_order(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'web');

        $m1 = Media::factory()->create();
        $m2 = Media::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id, 'content' => 'c']);

        $res1 = $this->patchJson("/api/posts/{$post->id}", [
            'media' => [
                ['id' => $m1->id, 'order' => 1],
                ['id' => $m2->id, 'order' => 0],
            ],
        ]);
        $this->assertStatusAndDump($res1, 200);

        $m3 = Media::factory()->create();
        $res2 = $this->patchJson("/api/posts/{$post->id}", [
            'media' => [
                ['id' => $m3->id, 'order' => 0],
                ['id' => $m1->id, 'order' => 1],
            ],
        ]);
        $this->assertStatusAndDump($res2, 200);

        $show = $this->getJson("/api/posts/{$post->id}");
        $this->assertStatusAndDump($show, 200);
        $this->assertSame($m3->id, $show->json('data.media.0.id'));
        $this->assertSame($m1->id, $show->json('data.media.1.id'));
        $this->assertSame(2, count($show->json('data.media')));
    }
}
