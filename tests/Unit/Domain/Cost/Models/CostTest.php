<?php

use Domain\Cost\Models\Cost;
use Domain\Cost\Models\CostCategory;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('belongs to user and category', function () {
    $user = User::factory()->create();
    $category = CostCategory::query()->create([
        'title' => 'Shipping',
        'status' => 1,
        'description' => 'Logistics',
        'parent_id' => 0,
    ]);
    $cost = Cost::query()->create([
        'amount' => 150000,
        'user_id' => $user->id,
        'category_id' => $category->id,
        'description' => 'Courier fee',
        'status' => Cost::PENDING,
        'image' => null,
    ]);

    expect($cost->user())->toBeInstanceOf(BelongsTo::class)
        ->and($cost->user->is($user))->toBeTrue()
        ->and($cost->category())->toBeInstanceOf(BelongsTo::class)
        ->and($cost->category->is($category))->toBeTrue();
});

it('casts amount and exposes status constants', function () {
    $user = User::factory()->create();
    $category = CostCategory::query()->create([
        'title' => 'Ads',
        'status' => 1,
        'parent_id' => 0,
    ]);
    $cost = Cost::query()->create([
        'amount' => 9999,
        'user_id' => $user->id,
        'category_id' => $category->id,
        'status' => Cost::PAID,
    ]);

    expect(Cost::PENDING)->toBe('pending')
        ->and(Cost::PAID)->toBe('paid')
        ->and($cost->status)->toBe(Cost::PAID)
        ->and((float) $cost->amount)->toBe(9999.0);
});

it('category can have parent and costs', function () {
    $parent = CostCategory::query()->create([
        'title' => 'Parent',
        'status' => 1,
        'parent_id' => 0,
    ]);
    $child = CostCategory::query()->create([
        'title' => 'Child',
        'status' => 1,
        'parent_id' => $parent->id,
    ]);
    $user = User::factory()->create();
    Cost::query()->create([
        'amount' => 1000,
        'user_id' => $user->id,
        'category_id' => $child->id,
        'status' => Cost::PENDING,
    ]);

    expect($child->parent->is($parent))->toBeTrue()
        ->and($child->costs)->toHaveCount(1);
});
