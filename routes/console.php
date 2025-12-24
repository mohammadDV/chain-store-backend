<?php

use Domain\Product\Jobs\ExpirePendingOrdersJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Option 1: Run as a scheduled command (current implementation)
Schedule::command('orders:expire-pending')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/scheduler.log'));

// Option 2: Run as a queued job (uncomment to use with Horizon/Telescope)
// Schedule::job(new ExpirePendingOrdersJob())
//     ->hourly()
//     ->withoutOverlapping();

// Uncomment and set MAIL_ADMIN_EMAIL in .env to receive email notifications on failures
// ->emailOutputOnFailure(env('MAIL_ADMIN_EMAIL'));