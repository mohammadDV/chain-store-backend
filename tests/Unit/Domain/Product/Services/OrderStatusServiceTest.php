<?php

use Application\Api\User\Notifications\EmailNotification;
use Domain\Notification\Services\NotificationService;
use Domain\Product\Enums\OrderLedgerSource;
use Domain\Product\Enums\OrderLedgerType;
use Domain\Product\Exceptions\OrderAlreadyRefundedException;
use Domain\Product\Models\Order;
use Domain\Product\Models\OrderLedger;
use Domain\Product\Models\OrderProduct;
use Domain\Product\Models\Product;
use Domain\Product\Services\OrderStatusService;
use Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
});

it('records a ledger entry when transitioning order status', function () {
    $admin = User::factory()->admin()->create();
    $order = Order::factory()->create(['status' => Order::PAID]);

    app(OrderStatusService::class)->transition(
        order: $order,
        toStatus: Order::SHIPPED,
        source: OrderLedgerSource::Admin,
        actor: $admin,
        message: 'Packed and shipped',
    );

    $ledger = OrderLedger::query()->where('order_id', $order->id)->first();

    expect($order->fresh()->status)->toBe(Order::SHIPPED)
        ->and($ledger)->not->toBeNull()
        ->and($ledger->type)->toBe(OrderLedgerType::StatusChanged)
        ->and($ledger->from_status)->toBe(Order::PAID)
        ->and($ledger->to_status)->toBe(Order::SHIPPED)
        ->and($ledger->user_id)->toBe($admin->id)
        ->and($ledger->message)->toBe('Packed and shipped')
        ->and($ledger->source)->toBe(OrderLedgerSource::Admin);
});

it('blocks status changes when the order is already refunded', function () {
    $admin = User::factory()->admin()->create();
    $order = Order::factory()->create(['status' => Order::REFUNDED]);

    expect(fn () => app(OrderStatusService::class)->transition(
        order: $order,
        toStatus: Order::SHIPPED,
        source: OrderLedgerSource::Admin,
        actor: $admin,
    ))->toThrow(OrderAlreadyRefundedException::class);

    expect(OrderLedger::query()->where('order_id', $order->id)->count())->toBe(0)
        ->and($order->fresh()->status)->toBe(Order::REFUNDED);
});

it('refunds an order into the wallet and writes a refunded ledger row', function () {
    $admin = User::factory()->admin()->create();
    $buyer = User::factory()->create();
    $wallet = $this->createWalletFor($buyer, 100);
    $product = Product::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $buyer->id,
        'status' => Order::PAID,
        'total_amount' => 1500,
    ]);
    $order->products()->attach($product->id, [
        'count' => 1,
        'amount' => 1500,
        'status' => Order::PAID,
    ]);

    Notification::fake();

    app(OrderStatusService::class)->transition(
        order: $order,
        toStatus: Order::REFUNDED,
        source: OrderLedgerSource::Admin,
        actor: $admin,
        message: 'Full refund',
    );

    expect($order->fresh()->status)->toBe(Order::REFUNDED)
        ->and((float) $wallet->fresh()->balance)->toBe(1600.0)
        ->and(OrderProduct::query()->where('order_id', $order->id)->where('status', Order::REFUNDED)->count())->toBe(1);

    $ledger = OrderLedger::query()->where('order_id', $order->id)->first();

    expect($ledger->type)->toBe(OrderLedgerType::Refunded)
        ->and($ledger->message)->toBe('Full refund');

    expect(Domain\Notification\Models\Notification::query()
        ->where('user_id', $buyer->id)
        ->where('model_type', NotificationService::ORDER)
        ->where('model_id', $order->id)
        ->exists())->toBeTrue();

    Notification::assertSentTo(
        $buyer,
        EmailNotification::class
    );
});

it('marks paid and records a paid ledger entry', function () {
    $customer = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $customer->id,
        'status' => Order::PENDING,
    ]);

    $result = app(OrderStatusService::class)->markPaid(
        order: $order,
        source: OrderLedgerSource::Customer,
        userId: $customer->id,
        meta: ['payment_method' => 'wallet'],
    );

    expect($result)->not->toBeNull()
        ->and($result->status)->toBe(Order::PAID);

    $ledger = OrderLedger::query()->where('order_id', $order->id)->first();

    expect($ledger->type)->toBe(OrderLedgerType::Paid)
        ->and($ledger->meta['payment_method'])->toBe('wallet');
});

it('returns null from markPaid when already paid', function () {
    $order = Order::factory()->create(['status' => Order::PAID]);

    $result = app(OrderStatusService::class)->markPaid($order);

    expect($result)->toBeNull()
        ->and(OrderLedger::query()->where('order_id', $order->id)->count())->toBe(0);
});

it('returns null from markPaid when order is not pending', function () {
    $order = Order::factory()->create(['status' => Order::SHIPPED]);

    $result = app(OrderStatusService::class)->markPaid($order);

    expect($result)->toBeNull()
        ->and($order->fresh()->status)->toBe(Order::SHIPPED);
});

it('does not refund twice when transition to refunded is called twice', function () {
    $admin = User::factory()->admin()->create();
    $buyer = User::factory()->create();
    $wallet = $this->createWalletFor($buyer, 100);
    $product = Product::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $buyer->id,
        'status' => Order::PAID,
        'total_amount' => 1500,
    ]);
    $order->products()->attach($product->id, [
        'count' => 1,
        'amount' => 1500,
        'status' => Order::PAID,
    ]);

    $service = app(OrderStatusService::class);
    $service->transition($order, Order::REFUNDED, OrderLedgerSource::Admin, $admin, 'first');

    expect(fn () => $service->transition($order->fresh(), Order::REFUNDED, OrderLedgerSource::Admin, $admin, 'second'))
        ->toThrow(OrderAlreadyRefundedException::class);

    expect((float) $wallet->fresh()->balance)->toBe(1600.0)
        ->and(OrderLedger::query()->where('order_id', $order->id)->where('type', OrderLedgerType::Refunded)->count())->toBe(1);
});

it('rejects refunding an unpaid order', function () {
    $admin = User::factory()->admin()->create();
    $buyer = User::factory()->create();
    $wallet = $this->createWalletFor($buyer, 100);
    $order = Order::factory()->create([
        'user_id' => $buyer->id,
        'status' => Order::PENDING,
        'total_amount' => 1500,
    ]);

    expect(fn () => app(OrderStatusService::class)->transition(
        $order,
        Order::REFUNDED,
        OrderLedgerSource::Admin,
        $admin,
    ))->toThrow(InvalidArgumentException::class);

    expect((float) $wallet->fresh()->balance)->toBe(100.0)
        ->and($order->fresh()->status)->toBe(Order::PENDING);
});

it('refunds line amount times count and delivery remainder', function () {
    $admin = User::factory()->admin()->create();
    $buyer = User::factory()->create();
    $wallet = $this->createWalletFor($buyer, 0);
    $product = Product::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $buyer->id,
        'status' => Order::PAID,
        'amount' => 2000,
        'delivery_amount' => 500,
        'discount_amount' => 0,
        'total_amount' => 2500,
    ]);
    $order->products()->attach($product->id, [
        'count' => 2,
        'amount' => 1000,
        'status' => Order::PAID,
    ]);

    $lineId = (int) $order->products()->first()->pivot->id;

    Notification::fake();

    app(OrderStatusService::class)->transitionLine(
        order: $order,
        orderProductId: $lineId,
        toStatus: Order::REFUNDED,
        source: OrderLedgerSource::Admin,
        actor: $admin,
    );

    expect($order->fresh()->status)->toBe(Order::REFUNDED)
        ->and((float) $wallet->fresh()->balance)->toBe(2500.0);

    expect(Domain\Notification\Models\Notification::query()
        ->where('user_id', $buyer->id)
        ->where('model_id', $order->id)
        ->count())->toBe(1);
});

it('caps line refunds at total_amount when order has a discount', function () {
    $admin = User::factory()->admin()->create();
    $buyer = User::factory()->create();
    $wallet = $this->createWalletFor($buyer, 0);
    $productA = Product::factory()->create();
    $productB = Product::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $buyer->id,
        'status' => Order::PAID,
        'amount' => 1000,
        'delivery_amount' => 0,
        'discount_amount' => 200,
        'total_amount' => 800,
    ]);
    $order->products()->attach($productA->id, [
        'count' => 1,
        'amount' => 600,
        'status' => Order::PAID,
    ]);
    $order->products()->attach($productB->id, [
        'count' => 1,
        'amount' => 400,
        'status' => Order::PAID,
    ]);

    $lineIds = $order->products()->get()->map(fn ($p) => (int) $p->pivot->id)->all();
    $service = app(OrderStatusService::class);

    $service->transitionLine(
        order: $order,
        orderProductId: $lineIds[0],
        toStatus: Order::REFUNDED,
        source: OrderLedgerSource::Admin,
        actor: $admin,
    );

    expect((float) $wallet->fresh()->balance)->toBe(600.0)
        ->and($order->fresh()->status)->toBe(Order::PAID);

    $service->transitionLine(
        order: $order->fresh(),
        orderProductId: $lineIds[1],
        toStatus: Order::REFUNDED,
        source: OrderLedgerSource::Admin,
        actor: $admin,
    );

    expect($order->fresh()->status)->toBe(Order::REFUNDED)
        ->and((float) $wallet->fresh()->balance)->toBe(800.0);
});

it('expires a pending order and records an expired ledger entry', function () {
    $order = Order::factory()->create(['status' => Order::PENDING]);

    $expired = app(OrderStatusService::class)->expire($order);

    expect($expired)->toBeTrue()
        ->and($order->fresh()->status)->toBe(Order::EXPIRED);

    $ledger = OrderLedger::query()->where('order_id', $order->id)->first();

    expect($ledger->type)->toBe(OrderLedgerType::Expired)
        ->and($ledger->source)->toBe(OrderLedgerSource::System);
});
