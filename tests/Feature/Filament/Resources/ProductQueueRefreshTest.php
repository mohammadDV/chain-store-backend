<?php

use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Filament\Resources\ProductResource\Pages\ViewProduct;
use Domain\AdminAccess\AdminPermission;
use Domain\Brand\Models\Brand;
use Domain\Product\Jobs\RefreshProductOnCartJob;
use Domain\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
    config()->set('product_scraper.cart_refresh.queue', 'high');
});

it('queues a scraper refresh from the product list action', function () {
    Queue::fake();

    $this->actingAsAdmin();
    $product = Product::factory()->create([
        'code' => 'SKU-100',
        'brand_id' => Brand::factory()->create()->id,
    ]);

    livewire(ListProducts::class)
        ->callTableAction('queue_refresh', $product)
        ->assertHasNoTableActionErrors()
        ->assertNotified();

    Queue::assertPushedOn('high', RefreshProductOnCartJob::class, function (RefreshProductOnCartJob $job) use ($product) {
        return $job->productId === (int) $product->id;
    });
});

it('queues a scraper refresh from the product view page', function () {
    Queue::fake();

    $this->actingAsAdmin();
    $product = Product::factory()->create([
        'code' => 'SKU-200',
        'brand_id' => Brand::factory()->create()->id,
    ]);

    livewire(ViewProduct::class, ['record' => $product->getRouteKey()])
        ->callAction('queue_refresh')
        ->assertHasNoActionErrors()
        ->assertNotified();

    Queue::assertPushedOn('high', RefreshProductOnCartJob::class, function (RefreshProductOnCartJob $job) use ($product) {
        return $job->productId === (int) $product->id;
    });
});

it('queues a scraper refresh from the product edit page', function () {
    Queue::fake();

    $this->actingAsAdmin();
    $product = Product::factory()->create([
        'code' => 'SKU-300',
        'brand_id' => Brand::factory()->create()->id,
    ]);

    livewire(EditProduct::class, ['record' => $product->getRouteKey()])
        ->callAction('queue_refresh')
        ->assertHasNoActionErrors()
        ->assertNotified();

    Queue::assertPushedOn('high', RefreshProductOnCartJob::class, function (RefreshProductOnCartJob $job) use ($product) {
        return $job->productId === (int) $product->id;
    });
});

it('hides queue refresh without auto_update permission', function () {
    $brand = Brand::factory()->create();
    $this->actingAsAdminWithPermissions(
        [AdminPermission::PRODUCTS_VIEW, AdminPermission::PRODUCTS_UPDATE],
        [(int) $brand->id],
    );

    $product = Product::factory()->create([
        'code' => 'SKU-400',
        'brand_id' => $brand->id,
    ]);

    livewire(ListProducts::class)
        ->assertTableActionHidden('queue_refresh', $product);
});
