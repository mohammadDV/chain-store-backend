<?php

use Domain\Brand\Models\Brand;
use Domain\Product\Models\Category;
use Domain\Product\Models\Color;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
});

it('lists active categories that have products', function () {
    [, $product] = $this->seedProductWithStock();
    $category = Category::factory()->create([
        'status' => 1,
        'parent_id' => 0,
        'title' => 'Shoes',
    ]);
    $product->categories()->attach($category->id);

    Category::factory()->create([
        'status' => 1,
        'parent_id' => 0,
        'title' => 'Empty Category',
    ]);

    $response = $this->getJson('/api/categories/active')->assertOk();

    $titles = collect($response->json('data') ?? $response->json())->pluck('title');
    expect($titles)->toContain('Shoes');
});

it('lists all categories', function () {
    Category::factory()->create(['status' => 1, 'parent_id' => 0, 'title' => 'Root']);

    $this->getJson('/api/categories/all')
        ->assertOk();
});

it('shows a category and its children', function () {
    $parent = Category::factory()->create(['status' => 1, 'parent_id' => 0, 'title' => 'Parent']);
    $child = Category::factory()->create(['status' => 1, 'parent_id' => $parent->id, 'title' => 'Child']);

    $this->getJson("/api/categories/{$parent->id}")
        ->assertOk();

    $children = $this->getJson("/api/categories/{$parent->id}/children")->assertOk();
    $titles = collect($children->json('data') ?? $children->json())->pluck('title');
    expect($titles)->toContain('Child');
});

it('lists colors and active colors', function () {
    $color = Color::factory()->create(['status' => 1, 'title' => 'Navy']);
    Color::factory()->create(['status' => 0, 'title' => 'Hidden']);

    $this->getJson('/api/colors')
        ->assertOk()
        ->assertJsonStructure(['data']);

    $active = $this->getJson('/api/colors/active')->assertOk();
    $titles = collect($active->json('data') ?? $active->json())->pluck('title');
    expect($titles)->toContain('Navy');
});

it('shows a color', function () {
    $color = Color::factory()->create(['status' => 1]);

    $this->getJson("/api/colors/{$color->id}")
        ->assertOk()
        ->assertJsonPath('id', $color->id);
});

it('filters active colors by brand when provided', function () {
    $brand = Brand::factory()->create();
    $color = Color::factory()->create(['status' => 1, 'title' => 'BrandColor']);
    $brand->colors()->attach($color->id, ['status' => 1, 'priority' => 1]);

    $response = $this->getJson("/api/colors/active/{$brand->id}")->assertOk();
    $titles = collect($response->json('data') ?? $response->json())->pluck('title');
    expect($titles)->toContain('BrandColor');
});
