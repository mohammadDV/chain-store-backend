<?php

use Domain\Product\Jobs\RefreshStaleProductsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('orders:expire-pending')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/scheduler.log'));

Schedule::job(new RefreshStaleProductsJob)
    ->everyFifteenMinutes()
    ->withoutOverlapping(30)
    ->onOneServer()
    ->when(fn () => (bool) config('product_scraper.stale_refresh.enabled'));
