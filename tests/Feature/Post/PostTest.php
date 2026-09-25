<?php

use Domain\Post\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
});

it('lists active posts publicly', function () {
    $active = Post::factory()->active()->create(['title' => 'Active Travel Guide']);
    Post::factory()->create(['status' => 0, 'title' => 'Hidden Post']);

    $response = $this->getJson('/api/posts')->assertOk();

    $titles = collect($response->json('data'))->pluck('title');
    expect($titles)->toContain($active->title)
        ->and($titles)->not->toContain('Hidden Post');
});

it('lists popular and latest posts', function () {
    Post::factory()->active()->create(['view' => 100]);
    Post::factory()->active()->create(['view' => 5]);

    $this->getJson('/api/posts/popular')->assertOk()->assertJsonStructure(['data']);
    $this->getJson('/api/posts/latest')->assertOk()->assertJsonStructure(['data']);
});

it('shows a post and increments view count', function () {
    $post = Post::factory()->active()->create(['view' => 3]);

    $this->getJson("/api/post/{$post->id}")
        ->assertOk()
        ->assertJsonPath('id', $post->id);

    expect($post->fresh()->view)->toBe(4);
});
