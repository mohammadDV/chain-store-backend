<?php

use Domain\Post\Models\Post;
use Domain\Product\Models\Category;
use Domain\Seo\Models\SeoRedirect;
use Domain\Setting\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
});

it('exposes seo fields when showing a product by slug', function () {
    [, $product] = $this->seedProductWithStock();
    $product->update([
        'meta_title' => 'Meta Product Title',
        'meta_description' => 'Meta product description',
        'meta_keywords' => 'shoe,sport',
    ]);
    $product->refresh();

    expect($product->slug)->not->toBeEmpty();

    $this->getJson("/api/products/{$product->slug}")
        ->assertOk()
        ->assertJsonPath('product.slug', $product->slug)
        ->assertJsonPath('product.meta_title', 'Meta Product Title')
        ->assertJsonPath('product.meta_description', 'Meta product description');
});

it('resolves categories by slug', function () {
    $category = Category::factory()->create(['title' => 'Running Shoes']);
    $category->refresh();

    $this->getJson("/api/categories/{$category->slug}")
        ->assertOk()
        ->assertJsonPath('id', $category->id)
        ->assertJsonPath('slug', $category->slug)
        ->assertJsonStructure(['description', 'meta_title', 'meta_description']);
});

it('resolves posts by slug', function () {
    $post = Post::factory()->active()->create([
        'title' => 'SEO Friendly Post',
        'meta_title' => 'Post Meta',
    ]);
    $post->refresh();

    $this->getJson("/api/post/{$post->slug}")
        ->assertOk()
        ->assertJsonPath('id', $post->id)
        ->assertJsonPath('slug', $post->slug)
        ->assertJsonPath('meta_title', 'Post Meta');
});

it('records a redirect when a post slug changes', function () {
    $post = Post::factory()->active()->create(['title' => 'Original Title']);
    $post->refresh();
    $oldSlug = $post->slug;

    $post->update(['slug' => 'new-post-slug']);

    expect(SeoRedirect::query()->where('from_path', '/post/'.$oldSlug)->exists())->toBeTrue();

    $this->getJson('/api/seo/redirects')
        ->assertOk()
        ->assertJsonFragment([
            'from_path' => '/post/'.$oldSlug,
            'to_path' => '/post/new-post-slug',
            'status_code' => 301,
        ]);
});

it('returns seo settings and sitemap payloads', function () {
    Setting::getInstance()->update([
        'site_name' => 'Boof Store',
        'default_meta_description' => 'Default description',
    ]);

    [, $product] = $this->seedProductWithStock();
    $product->refresh();
    Category::factory()->create(['title' => 'Sitemap Category']);
    Post::factory()->active()->create(['title' => 'Sitemap Post']);

    $this->getJson('/api/seo/settings')
        ->assertOk()
        ->assertJsonPath('data.site_name', 'Boof Store')
        ->assertJsonPath('data.default_meta_description', 'Default description');

    $this->getJson('/api/seo/sitemap')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'products',
                'categories',
                'posts',
                'brands',
            ],
        ]);
});
