<?php

use Domain\Product\Jobs\RefreshProductOnCartJob;
use Domain\Product\Models\Product;
use Domain\Setting\Services\SettingService;
use Domain\User\Services\TelegramNotificationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    config()->set('product.min_order_amount', 50000);
    config()->set('product_scraper.stale_refresh.hours', 48);
    config()->set('product_scraper.cart_refresh.queue', 'high');

    $this->mock(TelegramNotificationService::class);

    $this->mock(SettingService::class, function ($mock) {
        $mock->shouldReceive('getExchangeRateWithFallback')->andReturn(1.0);
        $mock->shouldReceive('getProfitRateWithFallback')->andReturn(0.0);
    });
});

it('queues a refresh on the high queue when the product is eligible', function () {
    Queue::fake();

    $product = makeBoundCartProduct();

    $this->postJson("/api/products/{$product->id}/refresh-on-cart")
        ->assertOk()
        ->assertJson([
            'status' => 1,
            'queued' => true,
        ]);

    Queue::assertPushedOn('high', RefreshProductOnCartJob::class, function (RefreshProductOnCartJob $job) use ($product) {
        return $job->productId === (int) $product->id;
    });
});

it('returns queued false without dispatching when the product is not stale', function () {
    Queue::fake();

    $product = makeBoundCartProduct([
        'updated_at' => now()->subHours(1),
    ]);

    $this->postJson("/api/products/{$product->id}/refresh-on-cart")
        ->assertOk()
        ->assertJson([
            'status' => 1,
            'queued' => false,
        ]);

    Queue::assertNothingPushed();
});

/**
 * @param  array<string, mixed>  $overrides
 */
function makeBoundCartProduct(array $overrides = []): Product
{
    $updatedAt = $overrides['updated_at'] ?? now()->subHours(72);
    unset($overrides['updated_at']);

    $product = new Product(array_merge([
        'title' => 'Feature product',
        'amount' => 100000,
        'discount' => 0,
        'active' => true,
        'status' => Product::COMPLETED,
        'code' => 'FEAT-1',
        'brand_id' => 1,
    ], $overrides));

    $product->id = 1001;
    $product->exists = true;
    $product->updated_at = $updatedAt instanceof Carbon
        ? $updatedAt
        : Carbon::parse($updatedAt);

    Route::bind('product', fn () => $product);

    return $product;
}
