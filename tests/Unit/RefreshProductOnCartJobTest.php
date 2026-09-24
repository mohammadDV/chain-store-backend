<?php

use Domain\Product\DTO\ProductRefreshOutcome;
use Domain\Product\Jobs\RefreshProductOnCartJob;
use Domain\Product\Services\StaleProductRefreshService;
use Illuminate\Contracts\Queue\ShouldBeUnique;

it('refreshes the product via refreshById', function () {
    $outcome = new ProductRefreshOutcome(
        productId: 7,
        code: 'X',
        brandId: 1,
        status: ProductRefreshOutcome::STATUS_SUCCESS,
        action: 'refresh',
    );

    $service = \Mockery::mock(StaleProductRefreshService::class);
    $service->shouldReceive('refreshById')
        ->once()
        ->with(7)
        ->andReturn($outcome);

    (new RefreshProductOnCartJob(7))->handle($service);
});

it('exits quietly when the product is missing', function () {
    $service = \Mockery::mock(StaleProductRefreshService::class);
    $service->shouldReceive('refreshById')
        ->once()
        ->with(99)
        ->andReturn(null);

    (new RefreshProductOnCartJob(99))->handle($service);
});

it('is unique per product id with scraper timeout as lock ttl', function () {
    config()->set('product_scraper.timeout', 180);

    $job = new RefreshProductOnCartJob(15);

    expect($job)->toBeInstanceOf(ShouldBeUnique::class)
        ->and($job->timeout)->toBe(180)
        ->and($job->uniqueFor)->toBe(180)
        ->and($job->tries)->toBe(1)
        ->and($job->uniqueId())->toBe('15')
        ->and($job->tags())->toBe([
            'products',
            'scraper',
            'cart-refresh',
            'product:15',
        ]);
});
