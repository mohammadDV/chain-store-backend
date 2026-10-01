<?php

use Domain\Brand\Models\Brand;
use Domain\Payment\Models\Transaction;
use Domain\Product\Models\Order;
use Domain\Product\Models\Product;
use Domain\Product\Models\Size;
use Domain\Product\Services\StockService;
use Domain\Setting\Models\Setting;
use Domain\Setting\Services\SettingService;
use Domain\User\Models\User;
use Domain\Wallet\Models\WalletTransaction;
use Illuminate\Container\Container;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Lang;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function createPendingOrderForGatewayTest($test, User $customer, int $quantity = 2, int $stock = 10): array
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

    return [$order, $product, $size];
}

beforeEach(function () {
    $this->seedRoles();
    app(SettingService::class)->clearCache();
});

it('exposes payment gateway enabled by default in public features', function () {
    Setting::getInstance();

    $this->getJson('/api/settings/features')
        ->assertOk()
        ->assertJsonPath('status', 1)
        ->assertJsonPath('data.payment_gateway_enabled', true)
        ->assertJsonPath('data.payment_gateway_disabled_message', null)
        ->assertJsonPath('data.delivery_amount', 0)
        ->assertJsonPath('data.limit_delivery_amount', 0);
});

it('exposes disabled message when payment gateway is off', function () {
    Setting::getInstance()->update(['payment_gateway_enabled' => false]);
    app(SettingService::class)->clearCache();

    $this->getJson('/api/settings/features')
        ->assertOk()
        ->assertJsonPath('data.payment_gateway_enabled', false)
        ->assertJsonPath(
            'data.payment_gateway_disabled_message',
            Lang::get('site.payment_gateway_disabled')
        );
});

it('rejects wallet top-up when payment gateway is disabled', function () {
    Setting::getInstance()->update(['payment_gateway_enabled' => false]);
    app(SettingService::class)->clearCache();

    $user = $this->actingAsUser();
    $this->createWalletFor($user, 0);

    $this->postJson('/api/profile/wallet/top-up', ['amount' => 100000])
        ->assertStatus(400)
        ->assertJsonPath('status', 0)
        ->assertJsonPath('message', Lang::get('site.payment_gateway_disabled'))
        ->assertJsonPath('payment_gateway_enabled', false);

    expect(WalletTransaction::query()->count())->toBe(0)
        ->and(Transaction::query()->count())->toBe(0);
});

it('rejects bank order payment when payment gateway is disabled', function () {
    Setting::getInstance()->update(['payment_gateway_enabled' => false]);
    app(SettingService::class)->clearCache();

    $customer = User::factory()->create();
    [$order] = createPendingOrderForGatewayTest($this, $customer);

    $this->postJson("/api/profile/orders/{$order->id}/pay", [
        'payment_method' => Transaction::BANK,
        'fullname' => 'Test Customer',
        'address' => 'Test address',
        'postal_code' => '1234567890',
    ])
        ->assertStatus(400)
        ->assertJsonPath('status', 0)
        ->assertJsonPath('message', Lang::get('site.payment_gateway_disabled'));

    expect(Transaction::query()->where('model_type', Transaction::ORDER)->count())->toBe(0)
        ->and($order->fresh()->status)->toBe(Order::PENDING);
});

it('still allows wallet order payment when payment gateway is disabled', function () {
    Setting::getInstance()->update(['payment_gateway_enabled' => false]);
    app(SettingService::class)->clearCache();

    $customer = User::factory()->create();
    $wallet = $this->createWalletFor($customer, 99_999_999_999);
    [$order] = createPendingOrderForGatewayTest($this, $customer, 2);

    $this->postJson("/api/profile/orders/{$order->id}/pay", [
        'payment_method' => Transaction::WALLET,
        'fullname' => 'Test Customer',
        'address' => 'Test address',
        'postal_code' => '1234567890',
    ])->assertCreated()->assertJsonPath('status', 1);

    expect($order->fresh()->status)->toBe(Order::PAID)
        ->and((float) $wallet->fresh()->balance)->toBeLessThan(99_999_999_999);
});

it('blocks the payment redirect endpoint when gateway is disabled', function () {
    Setting::getInstance()->update(['payment_gateway_enabled' => false]);
    app(SettingService::class)->clearCache();

    $transaction = Transaction::factory()->create();

    $this->get('/api/payment?'.http_build_query([
        'transaction' => $transaction->id,
        'sign' => Transaction::generateHash((string) $transaction->id),
    ]))
        ->assertStatus(503)
        ->assertSee(Lang::get('site.payment_gateway_disabled'), false);
});
