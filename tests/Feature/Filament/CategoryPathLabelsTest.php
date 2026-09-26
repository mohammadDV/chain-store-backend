<?php

use App\Filament\Resources\ProductResource\Pages\EditProduct;
use Domain\Product\Models\Category;
use Domain\Product\Models\Product;
use Domain\Product\Support\CategoryPathLabels;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
    $this->actingAsAdmin();
});

it('shows category breadcrumb labels on the product edit form options', function () {
    $root = Category::factory()->create(['title' => 'ورزشی', 'parent_id' => 0]);
    $mid = Category::factory()->childOf($root)->create(['title' => 'کوله']);
    $leaf = Category::factory()->childOf($mid)->create(['title' => 'کیف']);

    $product = Product::factory()->create();
    $product->categories()->attach($leaf->id);

    livewire(EditProduct::class, ['record' => $product->getKey()])
        ->assertSuccessful()
        ->assertFormSet([
            'categories' => [$leaf->id],
        ]);

    expect(CategoryPathLabels::forId($leaf->id))->toBe('ورزشی › کوله › کیف');
});
