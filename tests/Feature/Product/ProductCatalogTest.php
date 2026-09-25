<?php

use Domain\Product\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
});

it('searches products publicly', function () {
    [, $product] = $this->seedProductWithStock();
    $product->update(['title' => 'Unique Nike Runner']);

    $response = $this->postJson('/api/products/search', [
        'query' => 'Nike Runner',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['data', 'current_page', 'total']);

    $ids = collect($response->json('data'))->pluck('id');
    expect($ids)->toContain($product->id);
});

it('rejects invalid search filters with 422', function () {
    $this->postJson('/api/products/search', [
        'categories' => ['not-an-id'],
        'count' => 1,
        'sort' => 'invalid',
    ])->assertStatus(422)
        ->assertJsonPath('status', 0);
});

it('returns search suggestions', function () {
    [, $product] = $this->seedProductWithStock();
    $product->update(['title' => 'Suggestable Jacket']);

    $this->postJson('/api/products/search-suggestions', [
        'query' => 'Suggestable',
    ])->assertOk()
        ->assertJsonStructure(['products', 'categories']);
});

it('shows an active product', function () {
    [, $product] = $this->seedProductWithStock();

    $this->getJson("/api/products/{$product->id}")
        ->assertOk()
        ->assertJsonStructure(['product', 'reviews', 'related_products']);
});

it('returns 404 for inactive product show', function () {
    [, $product] = $this->seedProductWithStock();
    $product->update(['active' => 0]);

    $this->getJson("/api/products/{$product->id}")
        ->assertNotFound()
        ->assertJsonPath('status', 0);
});

it('returns featured products', function () {
    $this->seedProductWithStock();

    $this->postJson('/api/products/featured', [
        'column' => 'rate',
    ])->assertOk()
        ->assertJsonPath('status', 1)
        ->assertJsonStructure(['data']);
});

it('returns similar products', function () {
    [, $product] = $this->seedProductWithStock();
    $category = Category::factory()->create(['status' => 1, 'parent_id' => 0]);
    $product->categories()->attach($category->id);

    [, $similar] = $this->seedProductWithStock();
    $similar->categories()->attach($category->id);

    $this->getJson("/api/products/{$product->id}/similar")
        ->assertOk();
});
