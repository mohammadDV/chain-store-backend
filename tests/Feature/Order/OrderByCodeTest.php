<?php

use Domain\Product\Models\Order;
use Domain\Product\Models\Product;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns a public order summary by tracking code', function () {
    $buyer = User::factory()->create();
    $product = Product::factory()->create([
        'title' => 'Public Verify Product',
        'image' => 'products/sample.jpg',
    ]);

    $order = Order::factory()->create([
        'user_id' => $buyer->id,
        'status' => Order::PAID,
        'active' => 1,
        'amount' => 200000,
        'discount_amount' => 10000,
        'delivery_amount' => 30000,
        'total_amount' => 220000,
        'product_count' => 2,
        'profit' => 50000,
        'fullname' => 'Secret Customer',
        'address' => 'Secret Street 1',
        'postal_code' => '1234567890',
    ]);

    $order->products()->attach($product->id, [
        'count' => 2,
        'amount' => 100000,
        'status' => Order::PAID,
        'color_id' => null,
        'size_id' => null,
    ]);

    $this->getJson('/api/orders/by-code/'.$order->code)
        ->assertOk()
        ->assertJsonPath('status', 1)
        ->assertJsonPath('data.code', $order->code)
        ->assertJsonPath('data.status', Order::PAID)
        ->assertJsonPath('data.amount', 200000)
        ->assertJsonPath('data.total_amount', 220000)
        ->assertJsonPath('data.products.0.title', 'Public Verify Product')
        ->assertJsonPath('data.unlock_available', false)
        ->assertJsonPath('data.recipient_unlock_available', false)
        ->assertJsonMissingPath('data.fullname')
        ->assertJsonMissingPath('data.address')
        ->assertJsonMissingPath('data.postal_code')
        ->assertJsonMissingPath('data.profit')
        ->assertJsonMissingPath('data.user_id');
});

it('returns not found for unknown or inactive order codes', function () {
    $buyer = User::factory()->create();
    $inactive = Order::factory()->create([
        'user_id' => $buyer->id,
        'active' => 0,
        'status' => Order::PAID,
    ]);

    $this->getJson('/api/orders/by-code/'.$inactive->code)
        ->assertNotFound()
        ->assertJsonPath('status', 0);

    $this->getJson('/api/orders/by-code/1111222233334444')
        ->assertNotFound()
        ->assertJsonPath('status', 0);
});
