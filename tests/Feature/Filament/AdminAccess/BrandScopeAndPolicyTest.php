<?php

use App\Filament\Resources\ProductResource;
use App\Policies\ProductPolicy;
use Domain\AdminAccess\AdminPermission;
use Domain\Brand\Models\Brand;
use Domain\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
});

it('hides other brand products from scoped admins', function () {
    $allowed = Brand::factory()->create(['title' => 'Decathlon']);
    $other = Brand::factory()->create(['title' => 'Adidas']);
    $visible = Product::factory()->create(['brand_id' => $allowed->id]);
    $hidden = Product::factory()->create(['brand_id' => $other->id]);

    $this->actingAsAdminWithPermissions(
        [AdminPermission::PRODUCTS_VIEW],
        [$allowed->id],
    );

    $ids = ProductResource::getEloquentQuery()->pluck('id')->all();

    expect($ids)->toContain($visible->id)
        ->and($ids)->not->toContain($hidden->id);
});

it('denies product create without permission', function () {
    $user = $this->actingAsAdminWithPermissions([AdminPermission::PRODUCTS_VIEW]);

    expect((new ProductPolicy)->create($user))->toBeFalse()
        ->and(ProductResource::canCreate())->toBeFalse();
});

it('allows product create with permission', function () {
    $user = $this->actingAsAdminWithPermissions([
        AdminPermission::PRODUCTS_VIEW,
        AdminPermission::PRODUCTS_CREATE,
    ]);

    expect((new ProductPolicy)->create($user))->toBeTrue()
        ->and(ProductResource::canCreate())->toBeTrue();
});

it('denies editing a product outside brand scope via resource auth', function () {
    $allowed = Brand::factory()->create();
    $other = Brand::factory()->create();
    $product = Product::factory()->create(['brand_id' => $other->id]);

    $this->actingAsAdminWithPermissions(
        [AdminPermission::PRODUCTS_VIEW, AdminPermission::PRODUCTS_UPDATE],
        [$allowed->id],
    );

    expect(ProductResource::canEdit($product))->toBeFalse()
        ->and(ProductResource::canView($product))->toBeFalse();
});
