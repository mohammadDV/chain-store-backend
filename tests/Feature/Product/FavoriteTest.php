<?php

use Domain\Product\Models\Favorite;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
});

it('toggles product favorite for authenticated user', function () {
    $user = $this->actingAsUser();
    [, $product] = $this->seedProductWithStock();

    $this->postJson("/api/profile/products/{$product->id}/favorite")
        ->assertOk()
        ->assertJsonPath('status', 1)
        ->assertJsonPath('favorite', 1);

    expect(Favorite::where('user_id', $user->id)->where('product_id', $product->id)->exists())->toBeTrue();

    $this->postJson("/api/profile/products/{$product->id}/favorite")
        ->assertOk()
        ->assertJsonPath('favorite', 0);

    expect(Favorite::where('user_id', $user->id)->where('product_id', $product->id)->exists())->toBeFalse();
});

it('lists favorite products', function () {
    $user = $this->actingAsUser();
    [, $product] = $this->seedProductWithStock();

    Favorite::query()->create([
        'user_id' => $user->id,
        'product_id' => $product->id,
    ]);

    $response = $this->getJson('/api/profile/products/favorite')->assertOk();

    $ids = collect($response->json('data'))->pluck('id');
    expect($ids)->toContain($product->id);
});

it('rejects guest favorite access', function () {
    [, $product] = $this->seedProductWithStock();

    $this->postJson("/api/profile/products/{$product->id}/favorite")->assertUnauthorized();
    $this->getJson('/api/profile/products/favorite')->assertUnauthorized();
});
