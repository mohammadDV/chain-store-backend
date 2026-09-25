<?php

use Domain\Product\DTO\ProductRefreshOutcome;
use Domain\Product\DTO\StaleProductRefreshResult;

it('reports success and failed helpers', function () {
    $success = new ProductRefreshOutcome(
        productId: 1,
        code: 'ABC',
        brandId: 2,
        status: ProductRefreshOutcome::STATUS_SUCCESS,
        action: 'updated',
    );
    $failed = new ProductRefreshOutcome(
        productId: 3,
        code: null,
        brandId: null,
        status: ProductRefreshOutcome::STATUS_FAILED,
        reason: 'scraper error',
    );
    $skipped = new ProductRefreshOutcome(
        productId: 4,
        code: 'SKIP',
        brandId: 1,
        status: ProductRefreshOutcome::STATUS_SKIPPED,
        reason: 'no brand service',
    );

    expect($success->isSuccess())->toBeTrue()
        ->and($success->isFailed())->toBeFalse()
        ->and($failed->isSuccess())->toBeFalse()
        ->and($failed->isFailed())->toBeTrue()
        ->and($skipped->isSuccess())->toBeFalse()
        ->and($skipped->isFailed())->toBeFalse();
});

it('serializes outcome to array', function () {
    $outcome = new ProductRefreshOutcome(
        productId: 10,
        code: 'XYZ',
        brandId: 7,
        status: ProductRefreshOutcome::STATUS_SUCCESS,
        reason: null,
        action: 'refreshed',
    );

    expect($outcome->toArray())->toBe([
        'product_id' => 10,
        'code' => 'XYZ',
        'brand_id' => 7,
        'status' => 'success',
        'reason' => null,
        'action' => 'refreshed',
    ]);
});

it('builds empty stale refresh results', function () {
    $result = StaleProductRefreshResult::empty('No stale products');

    expect($result->isEmpty())->toBeTrue()
        ->and($result->selected)->toBe(0)
        ->and($result->succeeded)->toBe(0)
        ->and($result->failed)->toBe(0)
        ->and($result->skipped)->toBe(0)
        ->and($result->message)->toBe('No stale products')
        ->and($result->toArray()['outcomes'])->toBe([]);
});

it('aggregates outcomes into array and log context', function () {
    $success = new ProductRefreshOutcome(1, 'A', 1, ProductRefreshOutcome::STATUS_SUCCESS);
    $failed = new ProductRefreshOutcome(2, 'B', 1, ProductRefreshOutcome::STATUS_FAILED, 'boom');
    $skipped = new ProductRefreshOutcome(3, 'C', 1, ProductRefreshOutcome::STATUS_SKIPPED, 'skip');

    $result = new StaleProductRefreshResult(
        selected: 3,
        succeeded: 1,
        failed: 1,
        skipped: 1,
        outcomes: [$success, $failed, $skipped],
        message: 'done',
    );

    expect($result->isEmpty())->toBeFalse()
        ->and($result->toArray())->toMatchArray([
            'message' => 'done',
            'selected' => 3,
            'succeeded' => 1,
            'failed' => 1,
            'skipped' => 1,
        ])
        ->and($result->toArray()['outcomes'])->toHaveCount(3)
        ->and($result->toLogContext()['failures'])->toHaveCount(2)
        ->and($result->toLogContext()['failures'][0]['status'])->toBe('failed');
});
