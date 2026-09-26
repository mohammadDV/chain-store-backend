<?php

use Domain\Brand\Models\Brand;
use Domain\Payment\Models\Transaction;
use Domain\Product\Enums\OrderLedgerType;
use Domain\Product\Models\Order;
use Domain\Product\Models\OrderLedger;
use Domain\Product\Models\Product;
use Domain\Product\Models\Size;
use Domain\Product\Repositories\OrderRepository;
use Domain\Product\Services\StockService;
use Domain\User\Models\User;
use Illuminate\Container\Container;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function createPendingOrderForPaymentTest($test, User $customer, int $quantity = 2, int $stock = 10): array
{
    $brand = Brand::factory()->create(['has_stock_management' => 1]);
    $product = Product::factory()->create([
        'brand_id' => $brand->id,
        'user_id' => User::factory(),
        'amount' => 100_000,
        'active' => 1,
        'status' => Product::COMPLETED,
        'is_failed' => 0,
    ]);
    $size = Size::factory()->create([
        'product_id' => $product->id,
        'status' => 1,
    ]);
    Container::getInstance()->make(StockService::class)->setQuantity($size->id, $stock);
    $stockModel = $size->stock()->firstOrFail();
    Sanctum::actingAs($customer);

    $test->postJson('/api/profile/orders', [
        'products' => [
            ['id' => $product->id, 'count' => $quantity, 'size_id' => $size->id],
        ],
    ])->assertCreated()->assertJsonPath('status', 1);

    $order = Order::query()
        ->where('user_id', $customer->id)
        ->where('status', Order::PENDING)
        ->latest('id')
        ->firstOrFail();

    return [$order, $product, $size, $stockModel];
}

beforeEach(function () {
    $this->seedRoles();
});

it('creates an order with a product size', function () {
    $customer = User::factory()->create();
    [$order, $product, $size] = createPendingOrderForPaymentTest($this, $customer);

    $line = $order->products()->where('products.id', $product->id)->firstOrFail();

    expect($line->pivot->size_id)->toBe($size->id)
        ->and($line->pivot->count)->toBe(2)
        ->and(OrderLedger::query()->where('order_id', $order->id)->where('type', OrderLedgerType::Created)->exists())->toBeTrue();
});

it('pays an order with wallet balance', function () {
    $customer = User::factory()->create();
    $initialBalance = 99_999_999_999;
    $wallet = $this->createWalletFor($customer, $initialBalance);
    [$order] = createPendingOrderForPaymentTest($this, $customer, 2);

    $this->postJson("/api/profile/orders/{$order->id}/pay", [
        'payment_method' => Transaction::WALLET,
        'fullname' => 'Test Customer',
        'address' => 'Test address',
        'postal_code' => '1234567890',
    ])->assertCreated()->assertJsonPath('status', 1);

    expect($order->fresh()->status)->toBe(Order::PAID)
        ->and((float) $wallet->fresh()->balance)
        ->toBe($initialBalance - (float) $order->fresh()->total_amount)
        ->and(OrderLedger::query()->where('order_id', $order->id)->where('type', OrderLedgerType::Paid)->exists())->toBeTrue();
});

it('rejects wallet payment with insufficient balance', function () {
    $customer = User::factory()->create();
    $wallet = $this->createWalletFor($customer, 10_000);
    [$order] = createPendingOrderForPaymentTest($this, $customer);

    $this->postJson("/api/profile/orders/{$order->id}/pay", [
        'payment_method' => Transaction::WALLET,
        'fullname' => 'Test Customer',
        'address' => 'Test address',
        'postal_code' => '1234567890',
    ])->assertStatus(402)->assertJsonPath('status', 0);

    expect($order->fresh()->status)->toBe(Order::PENDING)
        ->and((float) $wallet->fresh()->balance)->toBe(10_000.0);
});

it('rejects paying another users order', function () {
    $owner = User::factory()->create();
    [$order] = createPendingOrderForPaymentTest($this, $owner);

    $attacker = User::factory()->create();
    $this->actingAsUser($attacker);
    $this->createWalletFor($attacker, 1_000_000);

    $this->postJson("/api/profile/orders/{$order->id}/pay", [
        'payment_method' => Transaction::WALLET,
        'fullname' => 'Attacker',
        'address' => 'Test address',
        'postal_code' => '1234567890',
    ])->assertUnauthorized()->assertJsonPath('message', 'Unauthorized');
});

it('rejects showing another users order', function () {
    $owner = User::factory()->create();
    [$order] = createPendingOrderForPaymentTest($this, $owner);

    $this->actingAsUser(User::factory()->create());

    $this->getJson("/api/profile/orders/{$order->id}")
        ->assertUnauthorized()
        ->assertJsonPath('message', 'Unauthorized');
});

it('returns a signed payment url for bank payment', function () {
    $customer = User::factory()->create();
    [$order] = createPendingOrderForPaymentTest($this, $customer);

    $response = $this->postJson("/api/profile/orders/{$order->id}/pay", [
        'payment_method' => Transaction::BANK,
        'fullname' => 'Test Customer',
        'address' => 'Test address',
        'postal_code' => '1234567890',
    ])->assertOk()->assertJsonPath('status', 1);

    $transaction = Transaction::query()
        ->where('model_type', Transaction::ORDER)
        ->where('model_id', $order->id)
        ->firstOrFail();

    $url = $response->json('url');

    expect($url)->toContain('transaction='.$transaction->id)
        ->and($url)->toContain('sign='.Transaction::generateHash((string) $transaction->id));
});

it('does not charge wallet twice when pay is called twice', function () {
    $customer = User::factory()->create();
    $initialBalance = 99_999_999_999;
    $wallet = $this->createWalletFor($customer, $initialBalance);
    [$order] = createPendingOrderForPaymentTest($this, $customer, 2);
    $payload = [
        'payment_method' => Transaction::WALLET,
        'fullname' => 'Test Customer',
        'address' => 'Test address',
        'postal_code' => '1234567890',
    ];

    $this->postJson("/api/profile/orders/{$order->id}/pay", $payload)
        ->assertCreated()
        ->assertJsonPath('status', 1);

    $this->postJson("/api/profile/orders/{$order->id}/pay", $payload)
        ->assertStatus(400)
        ->assertJsonPath('status', 0);

    $fresh = $order->fresh();

    expect($fresh->status)->toBe(Order::PAID)
        ->and((float) $wallet->fresh()->balance)->toBe($initialBalance - (float) $fresh->total_amount)
        ->and(OrderLedger::query()->where('order_id', $order->id)->where('type', OrderLedgerType::Paid)->count())->toBe(1);
});

it('reuses the same pending bank transaction on repeated pay clicks', function () {
    $customer = User::factory()->create();
    [$order] = createPendingOrderForPaymentTest($this, $customer);
    $payload = [
        'payment_method' => Transaction::BANK,
        'fullname' => 'Test Customer',
        'address' => 'Test address',
        'postal_code' => '1234567890',
    ];

    $first = $this->postJson("/api/profile/orders/{$order->id}/pay", $payload)
        ->assertOk()
        ->assertJsonPath('status', 1);

    $second = $this->postJson("/api/profile/orders/{$order->id}/pay", $payload)
        ->assertOk()
        ->assertJsonPath('status', 1);

    expect(Transaction::query()
        ->where('model_type', Transaction::ORDER)
        ->where('model_id', $order->id)
        ->where('status', Transaction::PENDING)
        ->count())->toBe(1)
        ->and($first->json('url'))->toBe($second->json('url'));
});

it('cancels pending bank transactions when paying with wallet', function () {
    $customer = User::factory()->create();
    $initialBalance = 99_999_999_999;
    $wallet = $this->createWalletFor($customer, $initialBalance);
    [$order] = createPendingOrderForPaymentTest($this, $customer, 1);

    $this->postJson("/api/profile/orders/{$order->id}/pay", [
        'payment_method' => Transaction::BANK,
        'fullname' => 'Test Customer',
        'address' => 'Test address',
        'postal_code' => '1234567890',
    ])->assertOk();

    expect(Transaction::query()
        ->where('model_id', $order->id)
        ->where('status', Transaction::PENDING)
        ->count())->toBe(1);

    $this->postJson("/api/profile/orders/{$order->id}/pay", [
        'payment_method' => Transaction::WALLET,
        'fullname' => 'Test Customer',
        'address' => 'Test address',
        'postal_code' => '1234567890',
    ])->assertCreated();

    expect($order->fresh()->status)->toBe(Order::PAID)
        ->and(Transaction::query()
            ->where('model_id', $order->id)
            ->where('status', Transaction::PENDING)
            ->count())->toBe(0)
        ->and(Transaction::query()
            ->where('model_id', $order->id)
            ->where('status', Transaction::CANCELLED)
            ->count())->toBe(1)
        ->and((float) $wallet->fresh()->balance)
        ->toBe($initialBalance - (float) $order->fresh()->total_amount);
});

it('does not decrement stock twice when completing an order twice', function () {
    $customer = User::factory()->create();
    [$order, , , $stock] = createPendingOrderForPaymentTest($this, $customer, 3, 10);

    $repository = $this->app->make(OrderRepository::class);
    expect($repository->completeOrder($order->id))->toBeTrue()
        ->and($repository->completeOrder($order->id))->toBeTrue();

    expect($order->fresh()->status)->toBe(Order::PAID)
        ->and($stock->fresh()->quantity)->toBe(7);
});
