<?php

use Domain\Product\Models\ManualOrder;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('belongs to an optional user', function () {
    $user = User::factory()->create();
    $order = ManualOrder::query()->create([
        'user_id' => $user->id,
        'code' => ManualOrder::generateCode(),
        'status' => ManualOrder::PENDING,
        'product_count' => 1,
        'amount' => 100_000,
        'delivery_amount' => 0,
        'total_amount' => 100_000,
        'profit' => 0,
        'profit_rate' => 40,
        'exchange_rate' => 3000,
        'active' => 1,
        'vip' => 0,
        'product_name' => 'Test product',
        'fullname' => 'Ali',
        'mobile' => '09120000000',
    ]);

    expect($order->user())->toBeInstanceOf(BelongsTo::class)
        ->and($order->user->is($user))->toBeTrue();

    $guestOrder = ManualOrder::query()->create([
        'user_id' => null,
        'code' => ManualOrder::generateCode(),
        'status' => ManualOrder::PENDING,
        'product_count' => 1,
        'amount' => 50_000,
        'delivery_amount' => 0,
        'total_amount' => 50_000,
        'profit' => 0,
        'profit_rate' => 40,
        'exchange_rate' => 3000,
        'active' => 1,
        'vip' => 0,
        'product_name' => 'Guest product',
        'fullname' => 'Guest',
        'mobile' => '09121111111',
    ]);

    expect($guestOrder->user_id)->toBeNull()
        ->and($guestOrder->user)->toBeNull();
});

it('generates a unique sixteen-digit order code', function () {
    $code = ManualOrder::generateCode();

    expect($code)->toMatch('/^\d{16}$/')
        ->and(ManualOrder::generateCode())->not->toBe($code);
});

it('exposes status constants and options', function () {
    expect(ManualOrder::PENDING)->toBe('pending')
        ->and(ManualOrder::PAID)->toBe('paid')
        ->and(ManualOrder::CANCELLED)->toBe('cancelled')
        ->and(ManualOrder::SHIPPED)->toBe('shipped')
        ->and(ManualOrder::DELIVERED)->toBe('delivered')
        ->and(ManualOrder::RETURNED)->toBe('returned')
        ->and(ManualOrder::REFUNDED)->toBe('refunded')
        ->and(ManualOrder::FAILED)->toBe('failed')
        ->and(ManualOrder::EXPIRED)->toBe('expired')
        ->and(ManualOrder::statusOptions())->toHaveKeys([
            ManualOrder::PENDING,
            ManualOrder::PAID,
            ManualOrder::REFUNDED,
        ]);
});
