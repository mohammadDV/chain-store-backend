<?php

use Domain\Brand\Models\Banner;
use Domain\Brand\Models\Brand;
use Domain\Product\Models\Color;
use Domain\Product\Models\Product;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('has many products', function () {
    $brand = Brand::factory()->create();
    $products = Product::factory()->count(2)->create(['brand_id' => $brand->id]);

    expect($brand->products())->toBeInstanceOf(HasMany::class)
        ->and($brand->products)->toHaveCount(2)
        ->and($brand->products->pluck('id')->all())
        ->toEqualCanonicalizing($products->pluck('id')->all());
});

it('has many banners', function () {
    $brand = Brand::factory()->create();
    $banner = Banner::query()->create([
        'title' => 'Summer',
        'brand_id' => $brand->id,
        'status' => 1,
        'priority' => 1,
    ]);

    expect($brand->banners())->toBeInstanceOf(HasMany::class)
        ->and($brand->banners)->toHaveCount(1)
        ->and($brand->banners->first()->is($banner))->toBeTrue();
});

it('belongs to many colors', function () {
    $brand = Brand::factory()->create();
    $color = Color::factory()->create();

    $brand->colors()->attach($color->id, ['priority' => 2, 'status' => 1]);

    expect($brand->colors())->toBeInstanceOf(BelongsToMany::class)
        ->and($brand->colors)->toHaveCount(1)
        ->and($brand->colors->first()->is($color))->toBeTrue();
});

it('supports stock management flag', function () {
    $managed = Brand::factory()->withStockManagement()->create();
    $plain = Brand::factory()->create(['has_stock_management' => 0]);

    expect($managed->has_stock_management)->toBe(1)
        ->and($plain->has_stock_management)->toBe(0);
});
