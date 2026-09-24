<?php

use Domain\Product\Jobs\RefreshProductOnCartJob;
use Domain\Product\Models\Product;
use Domain\Product\Services\CartProductRefreshService;
use Domain\Setting\Services\SettingService;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    config()->set('product.min_order_amount', 50000);
    config()->set('product_scraper.stale_refresh.hours', 48);
    config()->set('product_scraper.cart_refresh.queue', 'high');

    $this->mock(SettingService::class, function ($mock) {
        $mock->shouldReceive('getExchangeRateWithFallback')->andReturn(1.0);
        $mock->shouldReceive('getProfitRateWithFallback')->andReturn(0.0);
    });
});

function makeCartRefreshProduct(array $overrides = []): Product
{
    $product = new Product(array_merge([
        'title' => 'Sample',
        'amount' => 100000,
        'discount' => 0,
        'active' => true,
        'status' => Product::COMPLETED,
        'code' => 'ABC-123',
        'brand_id' => 2,
    ], $overrides));

    $product->id = $overrides['id'] ?? 42;
    $product->exists = true;
    $product->updated_at = $overrides['updated_at'] ?? now()->subHours(72);

    return $product;
}

it('considers an active completed stale product eligible', function () {
    $service = new CartProductRefreshService;

    expect($service->isEligible(makeCartRefreshProduct()))->toBeTrue();
});

it('rejects inactive products', function () {
    $service = new CartProductRefreshService;

    expect($service->isEligible(makeCartRefreshProduct(['active' => false])))->toBeFalse();
});

it('rejects non-completed products', function () {
    $service = new CartProductRefreshService;

    expect($service->isEligible(makeCartRefreshProduct(['status' => Product::PENDING])))->toBeFalse();
});

it('rejects products below the minimum order amount', function () {
    $service = new CartProductRefreshService;

    expect($service->isEligible(makeCartRefreshProduct(['amount' => 40000])))->toBeFalse();
});

it('rejects products updated within the stale window', function () {
    $service = new CartProductRefreshService;

    expect($service->isEligible(makeCartRefreshProduct([
        'updated_at' => now()->subHours(12),
    ])))->toBeFalse();
});

it('rejects products missing code or brand', function () {
    $service = new CartProductRefreshService;

    expect($service->isEligible(makeCartRefreshProduct(['code' => ''])))->toBeFalse();
    expect($service->isEligible(makeCartRefreshProduct(['brand_id' => null])))->toBeFalse();
});

it('dispatches the refresh job on the high queue when eligible', function () {
    Queue::fake();

    $service = new CartProductRefreshService;
    $queued = $service->dispatchIfEligible(makeCartRefreshProduct(['id' => 99]));

    expect($queued)->toBeTrue();

    Queue::assertPushedOn('high', RefreshProductOnCartJob::class, function (RefreshProductOnCartJob $job) {
        return $job->productId === 99;
    });
});

it('does not dispatch when the product is not eligible', function () {
    Queue::fake();

    $service = new CartProductRefreshService;
    $queued = $service->dispatchIfEligible(makeCartRefreshProduct(['active' => false]));

    expect($queued)->toBeFalse();
    Queue::assertNothingPushed();
});
