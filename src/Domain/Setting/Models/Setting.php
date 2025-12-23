<?php

namespace Domain\Setting\Models;

use Domain\Setting\Services\SettingService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'profit_rate',
        'exchange_rate',
    ];

    protected $casts = [
        'profit_rate' => 'decimal:2',
        'exchange_rate' => 'decimal:2',
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
            ]
        );
    }
}
