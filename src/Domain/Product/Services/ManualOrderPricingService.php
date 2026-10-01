<?php

namespace Domain\Product\Services;

use Domain\Setting\Services\SettingService;

/**
 * Manual-order money math mirrored from:
 * - Product::getAmountAttribute (foreign raw → IRR payable)
 * - OrderRepository order create/update (amount + delivery + profit snapshots)
 */
class ManualOrderPricingService
{
    public function __construct(
        private readonly SettingService $settingService,
    ) {}

    /**
     * Same formula as Product::getAmountAttribute (without product discount).
     */
    public function convertRawProductAmount(float $rawAmount): float
    {
        $rate = $this->settingService->getExchangeRateWithFallback();

        $payableAmount = $rawAmount * $rate;
        $profit = $payableAmount * $this->settingService->getProfitRateWithFallback() / 100;

        return (float) (ceil(($payableAmount + $profit) / 1000) * 1000);
    }

    /**
     * Same totals as OrderRepository when creating/updating an order (no discount).
     *
     * OrderRepository:
     *   $deliveryAmount = resolveDeliveryAmount((float) $productsAmount);
     *   $totalAmount = $productsAmount + $deliveryAmount;
     *   $profit = ($productsAmount * $profitRate / 100) + $deliveryAmount;
     *
     * @return array{
     *     amount: float,
     *     delivery_amount: float,
     *     total_amount: float,
     *     profit: float,
     *     profit_rate: float,
     *     exchange_rate: float
     * }
     */
    public function buildOrderAmounts(float $productsAmount): array
    {
        $deliveryAmount = $this->settingService->resolveDeliveryAmount((float) $productsAmount);
        $totalAmount = $productsAmount + $deliveryAmount;
        $profitRate = $this->settingService->getProfitRateWithFallback();

        return [
            'amount' => (float) $productsAmount,
            'delivery_amount' => (float) $deliveryAmount,
            'total_amount' => (float) $totalAmount,
            'profit' => (($productsAmount * $profitRate / 100) + $deliveryAmount),
            'profit_rate' => (float) $profitRate,
            'exchange_rate' => (float) $this->settingService->getExchangeRateWithFallback(),
        ];
    }

    /**
     * Full quote from a foreign raw price (accessor conversion + OrderRepository totals).
     *
     * @return array{
     *     raw_price: float,
     *     amount: float,
     *     delivery_amount: float,
     *     total_amount: float,
     *     profit: float,
     *     profit_rate: float,
     *     exchange_rate: float
     * }
     */
    public function quoteFromRawPrice(float $rawPrice): array
    {
        $productsAmount = $this->convertRawProductAmount($rawPrice);

        return array_merge(
            ['raw_price' => (float) $rawPrice],
            $this->buildOrderAmounts($productsAmount),
        );
    }

    public function formatMoney(float $amount): string
    {
        return number_format($amount, 0, '.', ',').' '.__('site.toman');
    }
}
