<?php

use Domain\Brand\Models\Brand;
use Domain\Product\Models\Category;
use Domain\Product\Models\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('defines a user relation', function () {
    $category = Category::factory()->create();

    expect($category->user())->toBeInstanceOf(BelongsTo::class);
});

it('belongs to many brands and products', function () {
    $category = Category::factory()->create();
    $brand = Brand::factory()->create();
    $product = Product::factory()->create();

    $category->brands()->attach($brand->id, ['priority' => 1, 'status' => 1]);
    $category->products()->attach($product->id);

    expect($category->brands)->toHaveCount(1)
        ->and($category->brands->first()->is($brand))->toBeTrue()
        ->and($category->brands->first()->pivot->priority)->toBe(1)
        ->and($category->products)->toHaveCount(1)
        ->and($category->products->first()->is($product))->toBeTrue();
});

it('supports parent and active children hierarchy', function () {
    $root = Category::factory()->create(['parent_id' => 0, 'status' => 1]);
    $child = Category::factory()->childOf($root)->create(['status' => 1]);
    $inactiveChild = Category::factory()->childOf($root)->create(['status' => 0]);
    $grandChild = Category::factory()->childOf($child)->create(['status' => 1]);

    expect($child->parent->is($root))->toBeTrue()
        ->and($root->children)->toHaveCount(1)
        ->and($root->children->first()->is($child))->toBeTrue()
        ->and($root->children->pluck('id'))->not->toContain($inactiveChild->id)
        ->and($root->hasChildren())->toBeTrue()
        ->and($grandChild->hasChildren())->toBeFalse();
});

it('detects root categories', function () {
    $rootNull = Category::factory()->make(['parent_id' => null]);
    $rootZero = Category::factory()->make(['parent_id' => 0]);
    $child = Category::factory()->make(['parent_id' => 5]);

    expect($rootNull->isRoot())->toBeTrue()
        ->and($rootZero->isRoot())->toBeTrue()
        ->and($child->isRoot())->toBeFalse();
});

it('builds breadcrumb path and depth', function () {
    $root = Category::factory()->create(['title' => 'Root', 'parent_id' => 0]);
    $mid = Category::factory()->childOf($root)->create(['title' => 'Mid']);
    $leaf = Category::factory()->childOf($mid)->create(['title' => 'Leaf']);

    expect($leaf->getPath())->toBe([
        ['id' => $root->id, 'title' => 'Root'],
        ['id' => $mid->id, 'title' => 'Mid'],
        ['id' => $leaf->id, 'title' => 'Leaf'],
    ])
        ->and($root->getDepth())->toBe(0)
        ->and($mid->getDepth())->toBe(1)
        ->and($leaf->getDepth())->toBe(2);
});

it('loads recursive children and parents', function () {
    $root = Category::factory()->create(['parent_id' => 0, 'status' => 1]);
    $child = Category::factory()->childOf($root)->create(['status' => 1]);
    Category::factory()->childOf($child)->create(['status' => 1]);

    $withChildren = Category::query()->with('childrenRecursive')->find($root->id);
    $withParents = Category::query()->with('parentRecursive')->find($child->id);

    expect($withChildren->childrenRecursive)->toHaveCount(1)
        ->and($withChildren->childrenRecursive->first()->childrenRecursive)->toHaveCount(1)
        ->and($withParents->parentRecursive->is($root))->toBeTrue();
});
