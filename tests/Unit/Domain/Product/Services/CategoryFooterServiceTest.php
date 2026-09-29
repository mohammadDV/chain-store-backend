<?php

use Domain\Product\Models\Category;
use Domain\Product\Services\CategoryFooterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::fake();
    Cache::forget(CategoryFooterService::CACHE_KEY);
});

it('builds footer columns from roots with unique child titles', function () {
    $categories = collect([
        (object) ['id' => 1, 'title' => 'مردانه', 'slug' => 'men', 'parent_id' => 0, 'priority' => 30],
        (object) ['id' => 2, 'title' => 'زنانه', 'slug' => 'women', 'parent_id' => 0, 'priority' => 20],
        (object) ['id' => 3, 'title' => 'کفش', 'slug' => 'men-shoes', 'parent_id' => 1, 'priority' => 10],
        (object) ['id' => 4, 'title' => 'کفش', 'slug' => 'men-shoes-dup', 'parent_id' => 1, 'priority' => 5],
        (object) ['id' => 5, 'title' => 'لباس', 'slug' => 'men-clothes', 'parent_id' => 1, 'priority' => 8],
        (object) ['id' => 6, 'title' => 'کیف', 'slug' => 'women-bag', 'parent_id' => 2, 'priority' => 9],
    ]);

    $columns = app(CategoryFooterService::class)->buildColumns($categories);

    expect($columns)->toHaveCount(2)
        ->and($columns[0]['title'])->toBe('مردانه')
        ->and($columns[0]['slug'])->toBe('men')
        ->and(collect($columns[0]['links'])->pluck('title')->all())->toBe(['کفش', 'لباس'])
        ->and($columns[0]['links'][0]['slug'])->toBe('men-shoes')
        ->and($columns[1]['title'])->toBe('زنانه')
        ->and(collect($columns[1]['links'])->pluck('title')->all())->toBe(['کیف']);
});

it('limits category columns and links per column', function () {
    $categories = collect();

    for ($i = 1; $i <= 5; $i++) {
        $categories->push((object) [
            'id' => $i,
            'title' => "Root {$i}",
            'slug' => "root-{$i}",
            'parent_id' => 0,
            'priority' => 100 - $i,
        ]);
    }

    $rootId = 1;
    for ($i = 1; $i <= 15; $i++) {
        $categories->push((object) [
            'id' => 100 + $i,
            'title' => "Child {$i}",
            'slug' => "child-{$i}",
            'parent_id' => $rootId,
            'priority' => 50 - $i,
        ]);
    }

    $columns = app(CategoryFooterService::class)->buildColumns($categories);

    expect($columns)->toHaveCount(CategoryFooterService::MAX_CATEGORY_COLUMNS)
        ->and($columns[0]['title'])->toBe('Root 1')
        ->and($columns[0]['links'])->toHaveCount(CategoryFooterService::MAX_LINKS_PER_COLUMN)
        ->and($columns[0]['links'][0]['title'])->toBe('Child 1');
});

it('ignores inactive categories and caches the result for a day', function () {
    $men = Category::factory()->create([
        'title' => 'مردانه',
        'status' => 1,
        'parent_id' => 0,
        'priority' => 10,
        'slug' => 'men-footer',
    ]);
    Category::factory()->create([
        'title' => 'کفش',
        'status' => 1,
        'parent_id' => $men->id,
        'priority' => 5,
        'slug' => 'men-shoes-footer',
    ]);
    Category::factory()->create([
        'title' => 'مخفی',
        'status' => 0,
        'parent_id' => $men->id,
        'priority' => 9,
        'slug' => 'hidden-footer',
    ]);

    $service = app(CategoryFooterService::class);
    $columns = $service->getColumns();

    expect($columns)->toHaveCount(1)
        ->and(collect($columns[0]['links'])->pluck('title')->all())->toBe(['کفش'])
        ->and(Cache::has(CategoryFooterService::CACHE_KEY))->toBeTrue();

    Category::query()->whereKey($men->id)->update(['title' => 'Stale Root']);

    expect($service->getColumns()[0]['title'])->toBe('مردانه');

    $men->refresh()->update(['title' => 'مردانه جدید']);

    expect($service->getColumns()[0]['title'])->toBe('مردانه جدید');
});

it('exposes footer categories via a public api', function () {
    $root = Category::factory()->create([
        'title' => 'ورزش',
        'status' => 1,
        'parent_id' => 0,
        'priority' => 1,
        'slug' => 'sport-footer',
    ]);
    Category::factory()->create([
        'title' => 'توپ',
        'status' => 1,
        'parent_id' => $root->id,
        'priority' => 1,
        'slug' => 'ball-footer',
    ]);

    $this->getJson('/api/categories/footer')
        ->assertOk()
        ->assertJsonPath('status', 1)
        ->assertJsonPath('data.0.title', 'ورزش')
        ->assertJsonPath('data.0.links.0.title', 'توپ')
        ->assertJsonPath('data.0.links.0.slug', 'ball-footer');
});

it('buildColumns is pure and does not hit the database', function () {
    $categories = new Collection([
        (object) ['id' => 1, 'title' => 'Root', 'slug' => 'root', 'parent_id' => 0, 'priority' => 1],
    ]);

    expect(app(CategoryFooterService::class)->buildColumns($categories))
        ->toBe([
            [
                'title' => 'Root',
                'slug' => 'root',
                'links' => [],
            ],
        ]);
});
