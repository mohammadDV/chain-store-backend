<?php

use Domain\Setting\Models\Setting;
use Domain\Setting\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::forget('app_settings');
});

it('returns settings from the singleton row', function () {
    Setting::query()->create([
        'id' => 1,
        'profit_rate' => 25.5,
        'exchange_rate' => 4200.25,
    ]);

    $service = app(SettingService::class);

    expect($service->getProfitRate())->toBe(25.5)
        ->and($service->getExchangeRate())->toBe(4200.25)
        ->and($service->getSettings())->toMatchArray([
            'profit_rate' => '25.50',
            'exchange_rate' => '4200.25',
        ]);
});

it('caches settings and clears cache on update', function () {
    Setting::getInstance();
    $service = app(SettingService::class);

    $first = $service->getSettings();
    Setting::query()->where('id', 1)->update([
        'profit_rate' => 99,
        'exchange_rate' => 1111,
    ]);

    // Cached value still returned until clear/updateSettings
    expect($service->getSettings())->toBe($first);

    $updated = $service->updateSettings([
        'profit_rate' => 33,
        'exchange_rate' => 3500,
    ]);

    expect((float) $updated->profit_rate)->toBe(33.0)
        ->and((float) $updated->exchange_rate)->toBe(3500.0)
        ->and($service->getProfitRate())->toBe(33.0)
        ->and(Cache::has('app_settings'))->toBeTrue();
});

it('falls back to config when settings lookup fails', function () {
    config([
        'setting.profit_rate' => 41,
        'setting.exchange_rate' => 3100,
    ]);

    $service = Mockery::mock(SettingService::class)->makePartial();
    $service->shouldReceive('getProfitRate')->andThrow(new RuntimeException('db down'));
    $service->shouldReceive('getExchangeRate')->andThrow(new RuntimeException('db down'));

    expect($service->getProfitRateWithFallback())->toBe(41.0)
        ->and($service->getExchangeRateWithFallback())->toBe(3100.0);
});

it('creates a default singleton when missing', function () {
    config([
        'setting.profit_rate' => 40,
        'setting.exchange_rate' => 3000,
    ]);

    $service = app(SettingService::class);

    expect($service->getProfitRate())->toBe(40.0)
        ->and($service->getExchangeRate())->toBe(3000.0)
        ->and(Setting::query()->count())->toBe(1);
});
