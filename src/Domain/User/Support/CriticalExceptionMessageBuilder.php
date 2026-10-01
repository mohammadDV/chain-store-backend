<?php

namespace Domain\User\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Throwable;

class CriticalExceptionMessageBuilder
{
    public const MAX_MESSAGE_LENGTH = 500;

    /**
     * Query / payload keys that must never appear in Telegram alerts.
     *
     * @var list<string>
     */
    private const SENSITIVE_KEYS = [
        'code',
        'token',
        'sign',
        'password',
        'access_token',
        'refresh_token',
        'client_secret',
        'api_key',
        'apikey',
        'authorization',
        'authority',
        'transactionid',
        'secret',
    ];

    public static function shouldNotify(Throwable $e): bool
    {
        if (! config('telegram.enabled', true)) {
            return false;
        }

        if ($e instanceof HttpExceptionInterface && $e->getStatusCode() < 500) {
            return false;
        }

        $current = $e;
        while ($current !== null) {
            if ($current instanceof TelegramSDKException) {
                return false;
            }

            $current = $current->getPrevious();
        }

        return true;
    }

    public static function build(Throwable $e): string
    {
        $status = $e instanceof HttpExceptionInterface
            ? (string) $e->getStatusCode()
            : '500';

        $request = request();
        $method = $request->method();
        $url = self::safeRequestUrl($request);
        $userId = Auth::id() ?? '-';

        $lines = [
            'Critical Error '.$status,
            'exception: '.$e::class,
            'message: '.self::sanitizeMessage($e->getMessage()),
            'file: '.$e->getFile(),
            'line: '.$e->getLine(),
            'request: '.$method.' '.$url,
            'user_id: '.$userId,
            'time: '.now()->toDateTimeString(),
            'stack:',
        ];

        foreach (array_slice($e->getTrace(), 0, 5) as $index => $frame) {
            $file = $frame['file'] ?? '[internal]';
            $line = $frame['line'] ?? '?';
            $call = ($frame['class'] ?? '').($frame['type'] ?? '').$frame['function'];
            $lines[] = '  #'.$index.' '.$file.':'.$line.($call !== '' ? ' '.$call : '');
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * Path only — never include query strings (OAuth codes, payment params).
     */
    public static function safeRequestUrl(?Request $request): string
    {
        if ($request === null) {
            return '-';
        }

        return $request->url();
    }

    public static function sanitizeMessage(string $message): string
    {
        if ($message === '') {
            return $message;
        }

        // Bearer tokens before generic key=value redaction.
        $message = preg_replace(
            '/\bBearer\s+[A-Za-z0-9\-._~+\/]+=*/i',
            'Bearer [REDACTED]',
            $message
        ) ?? $message;

        $keys = implode('|', array_map(
            static fn (string $key): string => preg_quote($key, '/'),
            self::SENSITIVE_KEYS
        ));

        $message = preg_replace(
            '/\b('.$keys.')\s*[:=]\s*([^\s&,;\'"\]}]+)/i',
            '$1=[REDACTED]',
            $message
        ) ?? $message;

        if (mb_strlen($message) > self::MAX_MESSAGE_LENGTH) {
            return mb_substr($message, 0, self::MAX_MESSAGE_LENGTH - 20)."\n…(truncated)";
        }

        return $message;
    }
}
