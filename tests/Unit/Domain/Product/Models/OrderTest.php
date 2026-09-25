<?php

use Domain\Payment\Models\Transaction;
use Domain\Product\Models\Discount;
use Domain\Product\Models\Order;
use Domain\Product\Models\Product;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('belongs to user and optional discount', function () {
    $user = User::factory()->create();
    $discount = Discount::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'discount_id' => $discount->id,
    ]);

    expect($order->user())->toBeInstanceOf(BelongsTo::class)
        ->and($order->user->is($user))->toBeTrue()
        ->and($order->discount())->toBeInstanceOf(BelongsTo::class)
        ->and($order->discount->is($discount))->toBeTrue();
});

it('attaches products with pivot data', function () {
    $order = Order::factory()->create();
    $product = Product::factory()->create();

    $order->products()->attach($product->id, [
        'count' => 2,
        'amount' => 50_000,
        'status' => Order::PENDING,
        'color_id' => null,
        'size_id' => null,
    ]);

    expect($order->products())->toBeInstanceOf(BelongsToMany::class)
        ->and($order->products)->toHaveCount(1)
        ->and($order->products->first()->pivot->count)->toBe(2)
        ->and((float) $order->products->first()->pivot->amount)->toBe(50_000.0);
});

it('has order transactions filtered by model type', function () {
    $order = Order::factory()->create();
    $orderTxn = Transaction::factory()->forOrder($order->id)->create([
        'status' => Transaction::PENDING,
    ]);
    Transaction::factory()->create([
        'model_type' => Transaction::WALLET,
        'model_id' => $order->id,
    ]);

    expect($order->transactions())->toBeInstanceOf(HasMany::class)
        ->and($order->transactions)->toHaveCount(1)
        ->and($order->transactions->first()->is($orderTxn))->toBeTrue();
});

it('generates a unique sixteen-digit order code', function () {
    $code = Order::generateCode();

    expect($code)->toMatch('/^\d{16}$/')
        ->and(Order::generateCode())->not->toBe($code);
});

it('exposes status constants', function () {
    expect(Order::PENDING)->toBe('pending')
        ->and(Order::PAID)->toBe('paid')
        ->and(Order::CANCELLED)->toBe('cancelled')
        ->and(Order::SHIPPED)->toBe('shipped')
        ->and(Order::DELIVERED)->toBe('delivered')
        ->and(Order::RETURNED)->toBe('returned')
        ->and(Order::REFUNDED)->toBe('refunded')
        ->and(Order::FAILED)->toBe('failed')
        ->and(Order::EXPIRED)->toBe('expired');
});

it('casts active to integer', function () {
    $order = Order::factory()->create(['active' => 1]);

    expect($order->active)->toBe(1);
});
