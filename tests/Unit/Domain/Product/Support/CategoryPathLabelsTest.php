<?php

use Domain\Product\Models\Category;
use Domain\Product\Support\CategoryPathLabels;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('builds full breadcrumb labels from a single query', function () {
    $root = Category::factory()->create(['title' => 'ورزشی', 'parent_id' => 0]);
    $mid = Category::factory()->childOf($root)->create(['title' => 'کوله']);
    $leaf = Category::factory()->childOf($mid)->create(['title' => 'کیف']);

    DB::enableQueryLog();
    DB::flushQueryLog();

    $labels = CategoryPathLabels::all();

    $queries = DB::getQueryLog();
    expect($queries)->toHaveCount(1)
        ->and($labels[$leaf->id])->toBe('ورزشی › کوله › کیف')
        ->and($labels[$mid->id])->toBe('ورزشی › کوله')
        ->and($labels[$root->id])->toBe('ورزشی');
});

it('reuses memoized labels without extra queries', function () {
    $root = Category::factory()->create(['title' => 'Root', 'parent_id' => 0]);
    $child = Category::factory()->childOf($root)->create(['title' => 'Child']);

    CategoryPathLabels::all();

    DB::enableQueryLog();
    DB::flushQueryLog();

    expect(CategoryPathLabels::for($child))->toBe('Root › Child')
        ->and(CategoryPathLabels::forId($root->id))->toBe('Root')
        ->and(CategoryPathLabels::options()[$child->id])->toBe('Root › Child');

    expect(DB::getQueryLog())->toHaveCount(0);
});

it('handles orphan parent ids without infinite loops', function () {
    $orphan = Category::factory()->create([
        'title' => 'Orphan',
        'parent_id' => 999999,
    ]);

    expect(CategoryPathLabels::for($orphan))->toBe('Orphan');
});
