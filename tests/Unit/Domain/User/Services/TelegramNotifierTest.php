<?php

use Domain\User\Jobs\SendTelegramMessageJob;
use Domain\User\Services\TelegramNotificationService;
use Domain\User\Services\TelegramNotifier;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\Support\Fakes\FakeTelegramNotificationService;

beforeEach(function () {
    config([
        'telegram.enabled' => true,
        'telegram.queue' => 'low',
        'telegram.channels.order' => '@boofstore_order_notification',
        'telegram.channels.error' => '@boofstore_error_notification',
    ]);
});

it('dispatches order notifications to the low queue and order channel', function () {
    Queue::fake();

    app(TelegramNotifier::class)->notifyOrder('hello order');

    Queue::assertPushedOn('low', SendTelegramMessageJob::class, function (SendTelegramMessageJob $job) {
        return $job->chatId === '@boofstore_order_notification'
            && $job->message === 'hello order';
    });
});

it('dispatches error notifications to the low queue and error channel', function () {
    Queue::fake();

    app(TelegramNotifier::class)->notifyError('hello error');

    Queue::assertPushedOn('low', SendTelegramMessageJob::class, function (SendTelegramMessageJob $job) {
        return $job->chatId === '@boofstore_error_notification'
            && $job->message === 'hello error';
    });
});

it('is a no-op when telegram notifications are disabled', function () {
    Queue::fake();
    config(['telegram.enabled' => false]);

    app(TelegramNotifier::class)->notifyOrder('should not send');

    Queue::assertNothingPushed();
});

it('is a no-op when chat id is empty', function () {
    Queue::fake();
    config(['telegram.channels.order' => '']);

    app(TelegramNotifier::class)->notifyOrder('should not send');

    Queue::assertNothingPushed();
});

it('sends truncated messages through the telegram service', function () {
    $fake = new FakeTelegramNotificationService;
    $this->app->instance(TelegramNotificationService::class, $fake);

    $long = str_repeat('x', SendTelegramMessageJob::MAX_MESSAGE_LENGTH + 50);
    $job = new SendTelegramMessageJob('@chat', $long);
    $job->handle($fake);

    expect($fake->messages)->toHaveCount(1)
        ->and(mb_strlen($fake->messages[0]))->toBeLessThanOrEqual(SendTelegramMessageJob::MAX_MESSAGE_LENGTH)
        ->and($fake->messages[0])->toContain('…(truncated)');
});

it('retries without rethrowing when telegram send fails', function () {
    Log::spy();

    $telegram = Mockery::mock(TelegramNotificationService::class);
    $telegram->shouldReceive('sendNotification')
        ->once()
        ->andThrow(new RuntimeException('telegram unreachable'));

    $job = Mockery::mock(SendTelegramMessageJob::class, ['@chat', 'hi'])->makePartial();
    $job->shouldReceive('attempts')->andReturn(1);
    $job->shouldReceive('release')->once()->with(10);

    $job->handle($telegram);

    Log::shouldHaveReceived('warning')->once();
});

it('truncates long messages statically', function () {
    $long = str_repeat('a', SendTelegramMessageJob::MAX_MESSAGE_LENGTH + 10);

    expect(SendTelegramMessageJob::truncate($long))->toContain('…(truncated)')
        ->and(mb_strlen(SendTelegramMessageJob::truncate($long)))->toBeLessThanOrEqual(SendTelegramMessageJob::MAX_MESSAGE_LENGTH);
});
