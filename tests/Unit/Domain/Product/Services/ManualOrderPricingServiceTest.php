<?php

use Domain\Product\Models\Product;
use Domain\Product\Services\ManualOrderPricingService;
use Domain\Setting\Models\Setting;
use Domain\Setting\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(SettingService::class)->clearCache();
});

it('converts raw price exactly like Product amount accessor', function () {
    Setting::getInstance()->update([
        'exchange_rate' => 3000,
        'profit_rate' => 40,
        'delivery_amount' => 180_000,
        'limit_delivery_amount' => 2_000_000,
    ]);
    app(SettingService::class)->clearCache();

    $product = Product::factory()->create(['amount' => 100, 'discount' => 0]);
    $converted = app(ManualOrderPricingService::class)->convertRawProductAmount(100);

    expect($converted)->toBe((float) $product->amount)
        ->and($converted)->toBe(420000.0);
});

it('builds order amounts exactly like OrderRepository without discount', function () {
    Setting::getInstance()->update([
        'exchange_rate' => 3000,
        'profit_rate' => 40,
        'delivery_amount' => 180_000,
        'limit_delivery_amount' => 2_000_000,
    ]);
    app(SettingService::class)->clearCache();

    $settings = app(SettingService::class);
    $productsAmount = 420_000.0;
    $deliveryAmount = $settings->resolveDeliveryAmount($productsAmount);
    $profitRate = $settings->getProfitRateWithFallback();

    $totals = app(ManualOrderPricingService::class)->buildOrderAmounts($productsAmount);

    expect($totals['amount'])->toBe($productsAmount)
        ->and($totals['delivery_amount'])->toBe($deliveryAmount)
        ->and($totals['delivery_amount'])->toBe(180000.0)
        ->and($totals['total_amount'])->toBe($productsAmount + $deliveryAmount)
        ->and($totals['profit'])->toBe(($productsAmount * $profitRate / 100) + $deliveryAmount)
        ->and($totals['profit_rate'])->toBe($profitRate)
        ->and($totals['exchange_rate'])->toBe($settings->getExchangeRateWithFallback());
});

it('waives delivery when products amount reaches free-shipping threshold like OrderRepository', function () {
    Setting::getInstance()->update([
        'exchange_rate' => 1,
        'profit_rate' => 0,
        'delivery_amount' => 180_000,
        'limit_delivery_amount' => 1_000_000,
    ]);
    app(SettingService::class)->clearCache();

    $below = app(ManualOrderPricingService::class)->buildOrderAmounts(500_000);
    $atLimit = app(ManualOrderPricingService::class)->buildOrderAmounts(1_000_000);

    expect($below['delivery_amount'])->toBe(180000.0)
        ->and($below['total_amount'])->toBe(680000.0)
        ->and($atLimit['delivery_amount'])->toBe(0.0)
        ->and($atLimit['total_amount'])->toBe(1000000.0);
});

it('quotes from raw foreign price using accessor then OrderRepository totals', function () {
    Setting::getInstance()->update([
        'exchange_rate' => 3000,
        'profit_rate' => 40,
        'delivery_amount' => 400_000,
        'limit_delivery_amount' => 12_000_000,
    ]);
    app(SettingService::class)->clearCache();

    $quote = app(ManualOrderPricingService::class)->quoteFromRawPrice(1000);

    // 1000 * 3000 * 1.4 = 4_200_000 < 12_000_000 → delivery 400_000
    expect($quote['amount'])->toBe(4200000.0)
        ->and($quote['delivery_amount'])->toBe(400000.0)
        ->and($quote['total_amount'])->toBe(4600000.0)
        ->and($quote['profit'])->toBe((4200000.0 * 40 / 100) + 400000.0);
});

it('formats money with toman suffix', function () {
    $formatted = app(ManualOrderPricingService::class)->formatMoney(420000);

    expect($formatted)->toContain('420,000')
        ->and($formatted)->toContain(__('site.toman'));
});
