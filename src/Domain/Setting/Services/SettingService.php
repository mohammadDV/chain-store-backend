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
     *
     * @return float
     */
    public function getProfitRate(): float
    {
        return (float) $this->getSettings()->profit_rate;
    }

    /**
     * Get amount rate (money rate) with caching.
     *
     * @return float
     */
    public function getExchangeRate(): float
    {
        return (float) $this->getSettings()->exchange_rate;
    }

    /**
     * Get all settings with caching.
     *
     * @return Setting
     */
    public function getSettings(): Setting
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return Setting::getInstance();
        });
    }

    /**
     * Update settings and clear cache.
     *
     * @param array $data
     * @return Setting
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
     *
     * @return void
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Get profit rate with fallback to config.
     *
     * @return float
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
     *
     * @return float
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
