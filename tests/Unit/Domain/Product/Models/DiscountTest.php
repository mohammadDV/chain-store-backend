<?php

use Domain\Product\Models\Discount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('is valid when active and unexpired', function () {
    expect(Discount::factory()->make([
        'active' => 1,
        'expire_date' => Carbon::now()->addDay()->toDateString(),
    ])->isValid())->toBeTrue();
});

it('is invalid when inactive or expired', function () {
    expect(Discount::factory()->inactive()->make()->isValid())->toBeFalse()
        ->and(Discount::factory()->expired()->make()->isValid())->toBeFalse();
});

it('calculates a percentage discount', function () {
    $discount = Discount::factory()->make([
        'type' => Discount::TYPE_PERCENTAGE,
        'value' => 15,
        'max_value' => null,
    ]);

    expect($discount->calculateDiscount(200_000))->toBe(30_000.0);
});

it('caps a percentage discount at its maximum value', function () {
    $discount = Discount::factory()->make([
        'type' => Discount::TYPE_PERCENTAGE,
        'value' => 25,
        'max_value' => 20_000,
    ]);

    expect($discount->calculateDiscount(100_000))->toBe(20_000.0);
});

it('calculates a fixed discount', function () {
    $discount = Discount::factory()->fixed(35_000)->make();

    expect($discount->calculateDiscount(200_000))->toBe(35_000.0);
});
