<?php

use Domain\Brand\Models\Brand;
use Domain\Post\Models\Post;
use Domain\Product\Models\Category;
use Domain\Product\Models\Product;
use Domain\Seo\Models\SeoRedirect;
use Domain\Seo\Services\SeoCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

it('caches the sitemap payload and serves from cache on subsequent reads', function () {
    Brand::factory()->create(['status' => 1, 'slug' => 'cached-brand']);
    Category::factory()->create(['status' => 1, 'slug' => 'cached-category']);
    Post::factory()->active()->create(['slug' => 'cached-post']);

    $service = app(SeoCacheService::class);

    $first = $service->getSitemap();
    expect(Cache::has(SeoCacheService::SITEMAP_CACHE_KEY))->toBeTrue();

    // Bypass model observers so the cache is not invalidated.
    DB::table('brands')->insert([
        'title' => 'Brand After Cache',
        'slug' => 'brand-after-cache',
        'status' => 1,
        'priority' => 1,
        'has_stock_management' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $second = $service->getSitemap();

    expect($second)->toBe($first)
        ->and(collect($second['brands'])->pluck('slug'))->not->toContain('brand-after-cache');
});

it('clears sitemap cache when a product is saved', function () {
    $product = Product::factory()->create(['slug' => 'seo-product', 'active' => 1]);

    app(SeoCacheService::class)->getSitemap();
    expect(Cache::has(SeoCacheService::SITEMAP_CACHE_KEY))->toBeTrue();

    $product->update(['title' => 'Updated for SEO cache bust']);

    expect(Cache::has(SeoCacheService::SITEMAP_CACHE_KEY))->toBeFalse();
});

it('caches redirects and clears the cache when a redirect changes', function () {
    SeoRedirect::query()->create([
        'from_path' => '/old',
        'to_path' => '/new',
        'status_code' => 301,
    ]);

    $service = app(SeoCacheService::class);
    $first = $service->getRedirects();

    expect($first)->toHaveCount(1)
        ->and(Cache::has(SeoCacheService::REDIRECTS_CACHE_KEY))->toBeTrue();

    SeoRedirect::query()->create([
        'from_path' => '/another',
        'to_path' => '/elsewhere',
        'status_code' => 301,
    ]);

    expect(Cache::has(SeoCacheService::REDIRECTS_CACHE_KEY))->toBeFalse();

    $second = $service->getRedirects();
    expect($second)->toHaveCount(2);
});
