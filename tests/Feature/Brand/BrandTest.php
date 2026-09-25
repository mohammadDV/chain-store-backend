<?php

use Domain\Brand\Models\Banner;
use Domain\Brand\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
});

it('lists active brands publicly', function () {
    $active = Brand::factory()->create(['status' => 1, 'priority' => 10]);
    Brand::factory()->create(['status' => 0, 'priority' => 20]);

    $response = $this->getJson('/api/brands')->assertOk();

    $ids = collect($response->json())->pluck('id');
    expect($ids)->toContain($active->id)
        ->and($ids)->not->toContain(
            Brand::query()->where('status', 0)->value('id')
        );
});

it('shows a brand', function () {
    $brand = Brand::factory()->create(['status' => 1]);

    $this->getJson("/api/brands/{$brand->id}")
        ->assertOk()
        ->assertJsonPath('id', $brand->id);
});

it('returns global banners when brand is omitted', function () {
    Banner::query()->create([
        'title' => 'Global Banner',
        'link' => 'https://example.com',
        'image' => 'banners/global.jpg',
        'status' => 1,
        'priority' => 1,
        'brand_id' => null,
    ]);

    $brand = Brand::factory()->create();
    Banner::query()->create([
        'title' => 'Brand Banner',
        'link' => 'https://example.com/brand',
        'image' => 'banners/brand.jpg',
        'status' => 1,
        'priority' => 1,
        'brand_id' => $brand->id,
    ]);

    $response = $this->postJson('/api/banners')->assertOk();

    $titles = collect($response->json())->pluck('title');
    expect($titles)->toContain('Global Banner')
        ->and($titles)->not->toContain('Brand Banner');
});

it('returns brand banners when brand is provided', function () {
    $brand = Brand::factory()->create();
    Banner::query()->create([
        'title' => 'Brand Only',
        'link' => null,
        'image' => 'banners/brand-only.jpg',
        'status' => 1,
        'priority' => 1,
        'brand_id' => $brand->id,
    ]);

    $response = $this->postJson('/api/banners', [
        'brand' => $brand->id,
    ])->assertOk();

    expect(collect($response->json())->pluck('title'))->toContain('Brand Only');
});
