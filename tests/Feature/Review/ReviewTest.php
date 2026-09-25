<?php

use Domain\Review\Models\Review;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
});

it('lists approved reviews for a product publicly', function () {
    [, $product] = $this->seedProductWithStock();
    $user = User::factory()->create();

    Review::query()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'comment' => 'Great product',
        'rate' => 5,
        'status' => Review::APPROVED,
        'active' => 1,
    ]);

    Review::query()->create([
        'user_id' => User::factory()->create()->id,
        'product_id' => $product->id,
        'comment' => 'Pending review',
        'rate' => 3,
        'status' => Review::PENDING,
        'active' => 1,
    ]);

    $response = $this->getJson("/api/products/{$product->id}/reviews")->assertOk();

    $comments = collect($response->json('data'))->pluck('comment');
    expect($comments)->toContain('Great product')
        ->and($comments)->not->toContain('Pending review');
});

it('allows authenticated user to store a review', function () {
    $user = $this->actingAsUser();
    [, $product] = $this->seedProductWithStock();

    $this->postJson("/api/profile/reviews/{$product->id}", [
        'comment' => 'Really nice quality',
        'rate' => 4,
    ])->assertCreated()
        ->assertJsonPath('status', 1);

    expect(Review::where('user_id', $user->id)->where('product_id', $product->id)->exists())->toBeTrue();
});

it('rejects duplicate reviews', function () {
    $user = $this->actingAsUser();
    [, $product] = $this->seedProductWithStock();

    Review::query()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'comment' => 'First',
        'rate' => 5,
        'status' => Review::APPROVED,
        'active' => 1,
    ]);

    $this->postJson("/api/profile/reviews/{$product->id}", [
        'comment' => 'Second attempt',
        'rate' => 3,
    ])->assertStatus(422)
        ->assertJsonPath('status', 0);
});

it('allows owner to update own review', function () {
    $user = $this->actingAsUser();
    [, $product] = $this->seedProductWithStock();

    $review = Review::query()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'comment' => 'Original',
        'rate' => 4,
        'status' => Review::APPROVED,
        'active' => 1,
    ]);

    $this->patchJson("/api/profile/reviews/{$review->id}", [
        'comment' => 'Updated comment',
        'rate' => 5,
    ])->assertOk()
        ->assertJsonPath('status', 1);

    expect($review->fresh()->comment)->toBe('Updated comment')
        ->and($review->fresh()->rate)->toBe(5);
});

it('cannot update another users review', function () {
    $owner = User::factory()->create();
    [, $product] = $this->seedProductWithStock();
    $review = Review::query()->create([
        'user_id' => $owner->id,
        'product_id' => $product->id,
        'comment' => 'Original review',
        'rate' => 4,
        'status' => Review::APPROVED,
        'active' => 1,
    ]);

    $this->actingAsUser(User::factory()->create());

    $this->patchJson("/api/profile/reviews/{$review->id}", [
        'comment' => 'Changed by attacker',
        'rate' => 1,
    ])->assertUnauthorized()
        ->assertJsonPath('message', 'Unauthorized');

    expect($review->fresh()->comment)->toBe('Original review');
});

it('rejects invalid review payload', function () {
    $this->actingAsUser();
    [, $product] = $this->seedProductWithStock();

    $this->postJson("/api/profile/reviews/{$product->id}", [
        'comment' => '',
        'rate' => 9,
    ])->assertStatus(422);
});

it('lists my reviews', function () {
    $user = $this->actingAsUser();
    [, $product] = $this->seedProductWithStock();

    Review::query()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'comment' => 'Mine',
        'rate' => 5,
        'status' => Review::APPROVED,
        'active' => 1,
    ]);

    $this->getJson('/api/profile/my-reviews')
        ->assertOk()
        ->assertJsonStructure(['data']);
});
