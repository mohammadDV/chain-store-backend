<?php

use Domain\Brand\Models\Brand;
use Domain\Product\Models\Order;
use Domain\Product\Models\Product;
use Domain\Product\Models\Size;
use Domain\Product\Services\StockService;
use Domain\Setting\Models\Setting;
use Domain\Setting\Services\SettingService;
use Domain\User\Models\User;
use Illuminate\Container\Container;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seedRoles();
    app(SettingService::class)->clearCache();
});

function createOrderWithProductAmount($test, User $customer, int $productAmount, int $quantity = 1): Order
{
    $brand = Brand::factory()->create(['has_stock_management' => 1]);
    $product = Product::factory()->create([
        'brand_id' => $brand->id,
        'user_id' => User::factory(),
        'amount' => $productAmount,
        'active' => 1,
        'status' => Product::COMPLETED,
        'is_failed' => 0,
    ]);
    $size = Size::factory()->create([
        'product_id' => $product->id,
        'status' => 1,
    ]);
    Container::getInstance()->make(StockService::class)->setQuantity($size->id, 20);
    Sanctum::actingAs($customer);

    $test->postJson('/api/profile/orders', [
        'products' => [
            ['id' => $product->id, 'count' => $quantity, 'size_id' => $size->id],
        ],
    ])->assertCreated()->assertJsonPath('status', 1);

    return Order::query()
        ->where('user_id', $customer->id)
        ->where('status', Order::PENDING)
        ->latest('id')
        ->firstOrFail();
}

it('charges delivery fee when order is below free-shipping threshold', function () {
    Setting::getInstance()->update([
        'exchange_rate' => 1,
        'profit_rate' => 0,
        'delivery_amount' => 180_000,
        'limit_delivery_amount' => 2_000_000,
    ]);
    app(SettingService::class)->clearCache();

    $customer = User::factory()->create();
    $order = createOrderWithProductAmount($this, $customer, 500_000);

    expect((float) $order->delivery_amount)->toBe(180000.0)
        ->and((float) $order->amount)->toBe(500000.0)
        ->and((float) $order->total_amount)->toBe(680000.0);
});

it('waives delivery fee when order reaches free-shipping threshold', function () {
    Setting::getInstance()->update([
        'exchange_rate' => 1,
        'profit_rate' => 0,
        'delivery_amount' => 180_000,
        'limit_delivery_amount' => 1_000_000,
    ]);
    app(SettingService::class)->clearCache();

    $customer = User::factory()->create();
    $order = createOrderWithProductAmount($this, $customer, 1_000_000);

    expect((float) $order->delivery_amount)->toBe(0.0)
        ->and((float) $order->amount)->toBe(1000000.0)
        ->and((float) $order->total_amount)->toBe(1000000.0);
});

it('exposes delivery settings in public features', function () {
    Setting::getInstance()->update([
        'delivery_amount' => 90_000,
        'limit_delivery_amount' => 750_000,
    ]);
    app(SettingService::class)->clearCache();

    $this->getJson('/api/settings/features')
        ->assertOk()
        ->assertJsonPath('status', 1)
        ->assertJsonPath('data.delivery_amount', 90000)
        ->assertJsonPath('data.limit_delivery_amount', 750000);
});

it('refreshes stale delivery on pending order show after settings change', function () {
    Setting::getInstance()->update([
        'exchange_rate' => 1,
        'profit_rate' => 0,
        'delivery_amount' => 200_000,
        'limit_delivery_amount' => 1_000_000,
    ]);
    app(SettingService::class)->clearCache();

    $customer = User::factory()->create();
    $order = createOrderWithProductAmount($this, $customer, 1_500_000);

    expect((float) $order->delivery_amount)->toBe(0.0)
        ->and((float) $order->total_amount)->toBe(1500000.0);

    Setting::getInstance()->update([
        'limit_delivery_amount' => 12_000_000,
        'delivery_amount' => 250_000,
    ]);
    app(SettingService::class)->clearCache();

    $response = $this->getJson("/api/profile/orders/{$order->id}")->assertOk();
    $payload = $response->json('data') ?? $response->json();

    expect((float) data_get($payload, 'delivery_amount'))->toBe(250000.0)
        ->and((float) data_get($payload, 'total_amount'))->toBe(1750000.0)
        ->and((float) $order->fresh()->delivery_amount)->toBe(250000.0)
        ->and((float) $order->fresh()->total_amount)->toBe(1750000.0);
});
