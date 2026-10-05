<?php

namespace Domain\Setting\Models;

use Core\Support\FrontendCacheInvalidator;
use Domain\Setting\Services\SettingService;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
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
        'telegram_username',
    ];

    protected $casts = [
        'profit_rate' => 'decimal:2',
        'exchange_rate' => 'decimal:2',
        'payment_gateway_enabled' => 'boolean',
        'delivery_amount' => 'decimal:2',
        'limit_delivery_amount' => 'decimal:2',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saved(function () {
            app(SettingService::class)->clearCache();
            app(FrontendCacheInvalidator::class)->revalidate([
                SettingService::CONTACT_CACHE_TAG,
            ]);
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
                'delivery_amount' => 0,
                'limit_delivery_amount' => 0,
            ]
        );
    }
}
