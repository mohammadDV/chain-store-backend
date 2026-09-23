<?php

namespace Domain\Setting\Services;

use Domain\Setting\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingService
{
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
     * Get all settings with caching.
     *
     * Only plain values are cached so the cache store never has to unserialize
     * PHP objects, which Laravel forbids by default since version 13.
     *
     * @return array{profit_rate: string, exchange_rate: string}
     */
    public function getSettings(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return Setting::getInstance()->only(['profit_rate', 'exchange_rate']);
        });
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
}
