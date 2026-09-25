<?php

use Domain\Payment\Models\Transaction;
use Domain\Product\Jobs\ExpirePendingOrdersJob;
use Domain\Product\Models\Order;
use Domain\Product\Repositories\Contracts\IOrderRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

it('expires pending orders past expire_date', function () {
    $expired = Order::factory()->create([
        'status' => Order::PENDING,
        'active' => 1,
        'expire_date' => now()->subMinute(),
    ]);
    $stillValid = Order::factory()->create([
        'status' => Order::PENDING,
        'active' => 1,
        'expire_date' => now()->addMinutes(30),
    ]);
    $paid = Order::factory()->create([
        'status' => Order::PAID,
        'active' => 1,
        'expire_date' => now()->subMinute(),
    ]);
    $inactive = Order::factory()->create([
        'status' => Order::PENDING,
        'active' => 0,
        'expire_date' => now()->subMinute(),
    ]);

    $count = app(IOrderRepository::class)->expirePendingOrders();

    expect($count)->toBe(1)
        ->and($expired->fresh()->status)->toBe(Order::EXPIRED)
        ->and($stillValid->fresh()->status)->toBe(Order::PENDING)
        ->and($paid->fresh()->status)->toBe(Order::PAID)
        ->and($inactive->fresh()->status)->toBe(Order::PENDING);
});

it('skips pending orders with a recent pending payment transaction', function () {
    $order = Order::factory()->create([
        'status' => Order::PENDING,
        'active' => 1,
        'expire_date' => now()->subMinute(),
    ]);
    Transaction::factory()->forOrder($order->id)->create([
        'status' => Transaction::PENDING,
        'created_at' => now()->subMinutes(5),
    ]);

    $count = app(IOrderRepository::class)->expirePendingOrders();

    expect($count)->toBe(0)
        ->and($order->fresh()->status)->toBe(Order::PENDING);
});

it('expires when pending payment transaction is older than fifteen minutes', function () {
    $order = Order::factory()->create([
        'status' => Order::PENDING,
        'active' => 1,
        'expire_date' => now()->subMinute(),
    ]);
    Transaction::factory()->forOrder($order->id)->create([
        'status' => Transaction::PENDING,
        'created_at' => now()->subMinutes(20),
    ]);

    $count = app(IOrderRepository::class)->expirePendingOrders();

    expect($count)->toBe(1)
        ->and($order->fresh()->status)->toBe(Order::EXPIRED);
});

it('runs through the expire pending orders job', function () {
    Order::factory()->create([
        'status' => Order::PENDING,
        'active' => 1,
        'expire_date' => now()->subMinutes(5),
    ]);

    Log::spy();

    (new ExpirePendingOrdersJob)->handle(app(IOrderRepository::class));

    expect(Order::query()->where('status', Order::EXPIRED)->count())->toBe(1);
    Log::shouldHaveReceived('info')->atLeast()->once();
});

it('rethrows repository failures from the job', function () {
    $repository = Mockery::mock(IOrderRepository::class);
    $repository->shouldReceive('expirePendingOrders')
        ->once()
        ->andThrow(new RuntimeException('expire failed'));

    Log::spy();

    expect(fn () => (new ExpirePendingOrdersJob)->handle($repository))
        ->toThrow(RuntimeException::class, 'expire failed');

    Log::shouldHaveReceived('error')->once();
});

it('logs permanently failed jobs', function () {
    Log::spy();

    (new ExpirePendingOrdersJob)->failed(new RuntimeException('permanent'));

    Log::shouldHaveReceived('error')->once();
});
