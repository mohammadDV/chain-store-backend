<?php

use Core\Exceptions\Handler;
use Domain\User\Services\TelegramNotifier;
use Domain\User\Support\CriticalExceptionMessageBuilder;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // $middleware->alias([
        //     'check.admin' => \Core\Http\Middleware\CheckAdmin::class,
        // ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (Throwable $e) {
            if (request()->is('api/*')) {
                return app(Handler::class)->handleApiException($e);
            }
        });

        $exceptions->reportable(function (Throwable $e) {
            if (! CriticalExceptionMessageBuilder::shouldNotify($e)) {
                return;
            }

            try {
                app(TelegramNotifier::class)->notifyError(
                    CriticalExceptionMessageBuilder::build($e)
                );
            } catch (Throwable) {
                // Never break exception reporting because of Telegram.
            }
        });
    })->create();
