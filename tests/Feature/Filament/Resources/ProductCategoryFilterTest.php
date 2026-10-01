<?php

use App\Filament\Resources\ProductResource\Pages\ListProducts;
use Domain\Product\Models\Category;
use Domain\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
    $this->actingAsAdmin();
});

it('filters the products table by selected category', function () {
    $category = Category::factory()->create(['title' => 'کتونی']);
    $otherCategory = Category::factory()->create(['title' => 'کوله']);

    $matched = Product::factory()->create(['title' => 'Matched Product']);
    $other = Product::factory()->create(['title' => 'Other Product']);

    $matched->categories()->attach($category->id);
    $other->categories()->attach($otherCategory->id);

    livewire(ListProducts::class)
        ->assertCanSeeTableRecords([$matched, $other])
        ->filterTable('category_id', $category->id)
        ->assertCanSeeTableRecords([$matched])
        ->assertCanNotSeeTableRecords([$other]);
});

it('shows all products when category filter is cleared', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->create();
    $product->categories()->attach($category->id);

    livewire(ListProducts::class)
        ->filterTable('category_id', $category->id)
        ->assertCanSeeTableRecords([$product])
        ->filterTable('category_id')
        ->assertCanSeeTableRecords([$product]);
});
