<?php

use Domain\Post\Models\Post;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class);

it('belongs to a user', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $user->id]);

    expect($post->user->is($user))->toBeTrue();
});

it('generates a slug from the title', function () {
    $post = Post::factory()->create([
        'title' => 'Unique Travel Guide Tehran',
        'slug' => null,
    ]);

    expect($post->slug)->not->toBeEmpty()
        ->and(strtolower($post->slug))->toContain('unique-travel-guide-tehran');
});

it('exposes status and type name attributes', function () {
    Config::set('custom.POST_TYPE', [0 => 'normal', 1 => 'video']);

    $active = Post::factory()->active()->create(['type' => 0]);
    $inactive = Post::factory()->create(['status' => 0, 'type' => 1]);

    expect($active->status_name)->toBe(__('site.Active'))
        ->and($inactive->status_name)->toBe(__('site.Inactive'))
        ->and($active->type_name)->toBe(__('site.normal'))
        ->and($inactive->type_name)->toBe(__('site.video'));
});

it('supports special and video states', function () {
    $special = Post::factory()->special()->create();
    $video = Post::factory()->video()->create();

    expect($special->special)->toBe(1)
        ->and($video->type)->toBe(1)
        ->and($video->video)->not->toBeNull();
});
