<?php

use Domain\Product\Models\Like;
use Domain\Product\Models\Product;
use Domain\Review\Models\Review;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('belongs to user and product', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();
    $review = Review::query()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'comment' => 'Nice shoes',
        'rate' => 5,
        'status' => Review::APPROVED,
        'active' => 1,
    ]);

    expect($review->user->is($user))->toBeTrue()
        ->and($review->product->is($product))->toBeTrue();
});

it('has morph many likes', function () {
    $review = Review::query()->create([
        'user_id' => User::factory()->create()->id,
        'product_id' => Product::factory()->create()->id,
        'comment' => 'Liked',
        'rate' => 4,
        'status' => Review::PENDING,
    ]);
    $like = Like::query()->create([
        'likeable_id' => $review->id,
        'likeable_type' => Review::class,
        'user_id' => User::factory()->create()->id,
        'is_like' => 1,
    ]);

    expect($review->likes)->toHaveCount(1)
        ->and($review->likes->first()->is($like))->toBeTrue();
});

it('exposes status constants', function () {
    expect(Review::PENDING)->toBe('pending')
        ->and(Review::APPROVED)->toBe('approved')
        ->and(Review::CANCELLED)->toBe('cancelled');
});

it('defaults to pending status', function () {
    $review = Review::query()->create([
        'user_id' => User::factory()->create()->id,
        'product_id' => Product::factory()->create()->id,
        'comment' => null,
        'rate' => 3,
    ])->refresh();

    expect($review->status)->toBe(Review::PENDING)
        ->and($review->comment)->toBeNull();
});
