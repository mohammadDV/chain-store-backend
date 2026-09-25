<?php

use Domain\Product\Models\Discount;
use Domain\Product\Repositories\DiscountRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns active visible discount and ignores expired', function () {
    Discount::factory()->create([
        'active' => 1,
        'visible' => 1,
        'expire_date' => now()->addWeek()->toDateString(),
        'code' => 'LIVE10',
    ]);
    Discount::factory()->expired()->create([
        'active' => 1,
        'visible' => 1,
        'code' => 'OLD10',
    ]);

    $response = app(DiscountRepository::class)->getActiveDiscount();

    expect($response->getStatusCode())->toBe(200);
    expect($response->getData(true)['status'])->toBe(1);
});

it('returns 404 when no active discount exists', function () {
    Discount::factory()->inactive()->create(['visible' => 1]);

    $response = app(DiscountRepository::class)->getActiveDiscount();

    expect($response->getStatusCode())->toBe(404);
    expect($response->getData(true)['status'])->toBe(0);
});
