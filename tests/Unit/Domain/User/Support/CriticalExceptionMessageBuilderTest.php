<?php

use Domain\User\Support\CriticalExceptionMessageBuilder;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Telegram\Bot\Exceptions\TelegramSDKException;

it('builds a message with exception class, message, file and line', function () {
    $exception = new RuntimeException('boom happened');

    $message = CriticalExceptionMessageBuilder::build($exception);

    expect($message)
        ->toContain('Critical Error 500')
        ->toContain(RuntimeException::class)
        ->toContain('boom happened')
        ->toContain($exception->getFile())
        ->toContain((string) $exception->getLine())
        ->toContain('stack:');
});

it('includes http status for http exceptions', function () {
    $exception = new HttpException(503, 'service unavailable');

    expect(CriticalExceptionMessageBuilder::build($exception))
        ->toContain('Critical Error 503')
        ->toContain('service unavailable');
});

it('includes request method and url when available', function () {
    $this->app->instance('request', Request::create('/api/test-path', 'POST'));

    $message = CriticalExceptionMessageBuilder::build(new RuntimeException('x'));

    expect($message)->toContain('request: POST')
        ->toContain('/api/test-path');
});

it('strips query strings from the request url', function () {
    $this->app->instance(
        'request',
        Request::create('https://example.com/api/auth/google/callback?code=SECRET_OAUTH&state=abc', 'GET')
    );

    $message = CriticalExceptionMessageBuilder::build(new RuntimeException('oauth failed'));

    expect($message)
        ->toContain('/api/auth/google/callback')
        ->not->toContain('SECRET_OAUTH')
        ->not->toContain('code=')
        ->not->toContain('state=');
});

it('redacts sensitive values in exception messages', function () {
    $message = CriticalExceptionMessageBuilder::build(new RuntimeException(
        'gateway failed token=abc123 and Authorization: Bearer eyJhbGciOiJIUzI1NiJ9.payload.sig'
    ));

    expect($message)
        ->toContain('token=[REDACTED]')
        ->toContain('[REDACTED]')
        ->not->toContain('abc123')
        ->not->toContain('eyJhbGciOiJIUzI1NiJ9')
        ->not->toContain('payload.sig');
});

it('truncates long exception messages', function () {
    $long = str_repeat('x', CriticalExceptionMessageBuilder::MAX_MESSAGE_LENGTH + 80);

    $message = CriticalExceptionMessageBuilder::build(new RuntimeException($long));

    expect($message)->toContain('…(truncated)')
        ->not->toContain($long);
});

it('should notify for unhandled runtime exceptions', function () {
    config(['telegram.enabled' => true]);

    expect(CriticalExceptionMessageBuilder::shouldNotify(new RuntimeException('fail')))->toBeTrue();
});

it('should not notify for client http exceptions', function () {
    config(['telegram.enabled' => true]);

    expect(CriticalExceptionMessageBuilder::shouldNotify(new NotFoundHttpException))->toBeFalse()
        ->and(CriticalExceptionMessageBuilder::shouldNotify(new HttpException(422, 'bad')))->toBeFalse();
});

it('should notify for server http exceptions', function () {
    config(['telegram.enabled' => true]);

    expect(CriticalExceptionMessageBuilder::shouldNotify(new HttpException(500, 'oops')))->toBeTrue();
});

it('should not notify when telegram is disabled', function () {
    config(['telegram.enabled' => false]);

    expect(CriticalExceptionMessageBuilder::shouldNotify(new RuntimeException('fail')))->toBeFalse();
});

it('should not notify for telegram sdk exceptions to avoid loops', function () {
    config(['telegram.enabled' => true]);

    expect(CriticalExceptionMessageBuilder::shouldNotify(
        new TelegramSDKException('telegram down')
    ))->toBeFalse();
});
