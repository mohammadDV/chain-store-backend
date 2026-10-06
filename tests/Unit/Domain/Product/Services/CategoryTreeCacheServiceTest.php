<?php

use Domain\Brand\Models\Brand;
use Domain\Product\Models\Category;
use Domain\Product\Repositories\CategoryRepository;
use Domain\Product\Services\CategoryTreeCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

it('caches allCategories and serves the same payload until cleared', function () {
    Category::factory()->create([
        'parent_id' => 0,
        'status' => 1,
        'priority' => 5,
        'title' => 'Root A',
    ]);

    $repo = app(CategoryRepository::class);
    $first = $repo->allCategories();

    expect(Cache::has(CategoryTreeCacheService::allKey(null)))->toBeTrue();

    // Bypass model observers so the tree cache is not invalidated.
    DB::table('categories')->insert([
        'title' => 'Root B After Cache',
        'slug' => 'root-b-after-cache',
        'parent_id' => 0,
        'status' => 1,
        'priority' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $second = $repo->allCategories();

    expect($second)->toBe($first)
        ->and(collect($second)->pluck('title'))->not->toContain('Root B After Cache');
});

it('clears category tree cache when a category is saved', function () {
    Category::factory()->create(['parent_id' => 0, 'status' => 1, 'title' => 'Cached Root']);

    app(CategoryRepository::class)->allCategories();
    expect(Cache::has(CategoryTreeCacheService::allKey(null)))->toBeTrue();

    Category::factory()->create(['parent_id' => 0, 'status' => 1, 'title' => 'Bust Cache']);

    expect(Cache::has(CategoryTreeCacheService::allKey(null)))->toBeFalse();
});

it('caches brand-scoped category trees separately', function () {
    $brand = Brand::factory()->create(['status' => 1]);
    $category = Category::factory()->create(['parent_id' => 0, 'status' => 1, 'title' => 'Brand Root']);
    $category->brands()->attach($brand->id, ['status' => 1, 'priority' => 1]);

    $repo = app(CategoryRepository::class);
    $repo->allCategories($brand);
    $repo->allCategories();

    expect(Cache::has(CategoryTreeCacheService::allKey($brand->id)))->toBeTrue()
        ->and(Cache::has(CategoryTreeCacheService::allKey(null)))->toBeTrue();
});
