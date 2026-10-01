<?php

namespace Domain\Setting\Services;

use Domain\Setting\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    public const CONTACT_CACHE_TAG = 'settings-contact';

    private const CACHE_KEY = 'app_settings';

    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Get profit rate with caching.
     */
    public function getProfitRate(): float
    {
        return (float) $this->getSettings()['profit_rate'];
    }

    /**
     * Get amount rate (money rate) with caching.
     */
    public function getExchangeRate(): float
    {
        return (float) $this->getSettings()['exchange_rate'];
    }

    /**
     * Whether the bank payment gateway is accepting new payments.
     * Defaults to true when the flag is missing (safe for old cache rows).
     */
    public function isPaymentGatewayEnabled(): bool
    {
        return (bool) $this->getSettings()['payment_gateway_enabled'];
    }

    /**
     * Flat shipping fee charged when the order is below the free-shipping threshold.
     * Missing values resolve to 0.
     */
    public function getDeliveryAmount(): float
    {
        return (float) $this->getSettings()['delivery_amount'];
    }

    /**
     * Order product subtotal at/above which shipping is free.
     * Missing values resolve to 0.
     */
    public function getLimitDeliveryAmount(): float
    {
        return (float) $this->getSettings()['limit_delivery_amount'];
    }

    /**
     * Resolve shipping fee for a given product subtotal.
     */
    public function resolveDeliveryAmount(float $productsAmount): float
    {
        if ($productsAmount < $this->getLimitDeliveryAmountWithFallback()) {
            return $this->getDeliveryAmountWithFallback();
        }

        return 0.0;
    }

    /**
     * Get all settings with caching.
     *
     * Only plain values are cached so the cache store never has to unserialize
     * PHP objects, which Laravel forbids by default since version 13.
     *
     * @return array{
     *     profit_rate: string,
     *     exchange_rate: string,
     *     payment_gateway_enabled: bool,
     *     delivery_amount: string,
     *     limit_delivery_amount: string,
     *     site_name: string|null,
     *     default_meta_description: string|null,
     *     default_og_image: string|null,
     *     contact_title: string|null,
     *     contact_subtitle: string|null,
     *     contact_phone: string|null,
     *     contact_phone_hours: string|null,
     *     contact_address: string|null,
     *     contact_map_url: string|null,
     *     contact_email: string|null,
     *     contact_email_hint: string|null
     * }
     */
    public function getSettings(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $settings = Setting::getInstance()->only([
                'profit_rate',
                'exchange_rate',
                'payment_gateway_enabled',
                'delivery_amount',
                'limit_delivery_amount',
                'site_name',
                'default_meta_description',
                'default_og_image',
                'contact_title',
                'contact_subtitle',
                'contact_phone',
                'contact_phone_hours',
                'contact_address',
                'contact_map_url',
                'contact_email',
                'contact_email_hint',
            ]);

            $settings['payment_gateway_enabled'] = (bool) $settings['payment_gateway_enabled'];

            return $settings;
        });
    }

    /**
     * Lightweight public flags for the storefront (cached via getSettings).
     *
     * @return array{
     *     payment_gateway_enabled: bool,
     *     payment_gateway_disabled_message: string|null,
     *     delivery_amount: float,
     *     limit_delivery_amount: float
     * }
     */
    public function getPublicFeatures(): array
    {
        $enabled = $this->isPaymentGatewayEnabled();

        return [
            'payment_gateway_enabled' => $enabled,
            'payment_gateway_disabled_message' => $enabled
                ? null
                : __('site.payment_gateway_disabled'),
            'delivery_amount' => $this->getDeliveryAmountWithFallback(),
            'limit_delivery_amount' => $this->getLimitDeliveryAmountWithFallback(),
        ];
    }

    /**
     * Public contact page fields (cached via getSettings).
     *
     * @return array{
     *     title: string,
     *     subtitle: string|null,
     *     phone: string|null,
     *     phone_hours: string|null,
     *     address: string|null,
     *     map_url: string|null,
     *     email: string|null,
     *     email_hint: string|null
     * }
     */
    public function getContactSettings(): array
    {
        $settings = $this->getSettings();

        return [
            'title' => $settings['contact_title'] ?: 'با ما در  ارتباط باشید',
            'subtitle' => $settings['contact_subtitle'] ?? 'ما میتوانیم به شما کمک کنیم!',
            'phone' => $settings['contact_phone'] ?? null,
            'phone_hours' => $settings['contact_phone_hours'] ?? null,
            'address' => $settings['contact_address'] ?? null,
            'map_url' => $settings['contact_map_url'] ?? null,
            'email' => $settings['contact_email'] ?? null,
            'email_hint' => $settings['contact_email_hint'] ?? null,
        ];
    }

    /**
     * @return array{
     *     site_name: string|null,
     *     default_meta_description: string|null,
     *     default_og_image: string|null
     * }
     */
    public function getSeoSettings(): array
    {
        $settings = $this->getSettings();

        return [
            'site_name' => $settings['site_name'] ?? null,
            'default_meta_description' => $settings['default_meta_description'] ?? null,
            'default_og_image' => $settings['default_og_image'] ?? null,
        ];
    }

    /**
     * Update settings and clear cache.
     */
    public function updateSettings(array $data): Setting
    {
        $setting = Setting::getInstance();
        $setting->update($data);

        $this->clearCache();

        return $setting->fresh();
    }

    /**
     * Clear settings cache.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Get profit rate with fallback to config.
     */
    public function getProfitRateWithFallback(): float
    {
        try {
            return $this->getProfitRate();
        } catch (\Exception $e) {
            return (float) config('setting.profit_rate');
        }
    }

    /**
     * Get amount rate with fallback to config.
     */
    public function getExchangeRateWithFallback(): float
    {
        try {
            return $this->getExchangeRate();
        } catch (\Exception $e) {
            return (float) config('setting.exchange_rate');
        }
    }

    /**
     * Get delivery fee from settings, or 0 when unavailable.
     */
    public function getDeliveryAmountWithFallback(): float
    {
        try {
            return $this->getDeliveryAmount();
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    /**
     * Get free-shipping threshold from settings, or 0 when unavailable.
     */
    public function getLimitDeliveryAmountWithFallback(): float
    {
        try {
            return $this->getLimitDeliveryAmount();
        } catch (\Exception $e) {
            return 0.0;
        }
    }
}
