<?php

namespace Domain\Setting\Models;

use Domain\Setting\Services\SettingService;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'profit_rate',
        'exchange_rate',
        'payment_gateway_enabled',
        'site_name',
        'default_meta_description',
        'default_og_image',
    ];

    protected $casts = [
        'profit_rate' => 'decimal:2',
        'exchange_rate' => 'decimal:2',
        'payment_gateway_enabled' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Clear cache when settings are updated or saved
        static::saved(function () {
            app(SettingService::class)->clearCache();
        });

        static::updated(function () {
            app(SettingService::class)->clearCache();
        });
    }

    /**
     * Get the singleton setting instance.
     * Since settings are typically a single row, this ensures we always work with the same record.
     */
    public static function getInstance(): self
    {
        return static::firstOrCreate(
            ['id' => 1],
            [
                'profit_rate' => config('setting.profit_rate', 40),
                'exchange_rate' => config('setting.exchange_rate', 3000),
                'payment_gateway_enabled' => config('setting.payment_gateway_enabled', true),
            ]
        );
    }
}
