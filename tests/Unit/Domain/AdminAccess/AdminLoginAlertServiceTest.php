<?php

use Domain\AdminAccess\Services\AdminLoginAlertService;
use Domain\User\Jobs\SendTelegramMessageJob;
use Domain\User\Services\TelegramNotifier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    config([
        'telegram.enabled' => true,
        'telegram.queue' => 'low',
        'telegram.channels.error' => '@boofstore_error_notification',
        'admin.login.decay_seconds' => 900,
    ]);

    Cache::flush();
});

it('dispatches a security telegram for failed admin logins', function () {
    Queue::fake();

    app(AdminLoginAlertService::class)->failedLogin('admin@example.com', 42);

    Queue::assertPushedOn('low', SendTelegramMessageJob::class, function (SendTelegramMessageJob $job) {
        return $job->chatId === '@boofstore_error_notification'
            && str_contains($job->message, 'Admin login failed')
            && str_contains($job->message, 'admin@example.com')
            && str_contains($job->message, 'user_id: 42')
            && ! str_contains(strtolower($job->message), 'password');
    });
});

it('dispatches a rate-limit telegram once per ip window', function () {
    Queue::fake();

    $service = app(AdminLoginAlertService::class);

    $service->rateLimited('admin@example.com');
    $service->rateLimited('admin@example.com');

    Queue::assertPushed(SendTelegramMessageJob::class, 1);
    Queue::assertPushedOn('low', SendTelegramMessageJob::class, function (SendTelegramMessageJob $job) {
        return str_contains($job->message, 'Admin login rate limited')
            && str_contains($job->message, 'admin@example.com');
    });
});

it('redacts invalid emails from alert payloads', function () {
    Queue::fake();

    app(AdminLoginAlertService::class)->failedLogin('not-an-email');

    Queue::assertPushed(SendTelegramMessageJob::class, function (SendTelegramMessageJob $job) {
        return str_contains($job->message, 'email: -')
            && ! str_contains($job->message, 'not-an-email');
    });
});

it('routes notifySecurity through the error telegram channel', function () {
    Queue::fake();

    app(TelegramNotifier::class)->notifySecurity('security ping');

    Queue::assertPushedOn('low', SendTelegramMessageJob::class, function (SendTelegramMessageJob $job) {
        return $job->chatId === '@boofstore_error_notification'
            && $job->message === 'security ping';
    });
});
