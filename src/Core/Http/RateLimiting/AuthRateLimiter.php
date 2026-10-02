<?php

namespace Core\Http\RateLimiting;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

final class AuthRateLimiter
{
    public const LOGIN_PER_MINUTE = 5;

    public const LOGIN_PER_HOUR = 20;

    public const REGISTER_PER_MINUTE = 3;

    public const REGISTER_PER_HOUR = 10;

    public const FORGOT_PASSWORD_MAX_ATTEMPTS = 1;

    public const FORGOT_PASSWORD_DECAY_MINUTES = 5;

    public static function configure(): void
    {
        RateLimiter::for('auth.login', function (Request $request) {
            $ip = (string) $request->ip();
            $email = Str::lower((string) $request->input('email'));

            return [
                Limit::perMinute(self::LOGIN_PER_MINUTE)
                    ->by('login:ip:'.$ip)
                    ->response(fn (Request $request, array $headers) => self::tooManyAttemptsResponse($headers)),
                Limit::perMinute(self::LOGIN_PER_MINUTE)
                    ->by('login:email:'.$email.'|'.$ip)
                    ->response(fn (Request $request, array $headers) => self::tooManyAttemptsResponse($headers)),
                Limit::perHour(self::LOGIN_PER_HOUR)
                    ->by('login:ip-hour:'.$ip)
                    ->response(fn (Request $request, array $headers) => self::tooManyAttemptsResponse($headers)),
            ];
        });

        RateLimiter::for('auth.register', function (Request $request) {
            $ip = (string) $request->ip();

            return [
                Limit::perMinute(self::REGISTER_PER_MINUTE)
                    ->by('register:ip:'.$ip)
                    ->response(fn (Request $request, array $headers) => self::tooManyAttemptsResponse($headers)),
                Limit::perHour(self::REGISTER_PER_HOUR)
                    ->by('register:ip-hour:'.$ip)
                    ->response(fn (Request $request, array $headers) => self::tooManyAttemptsResponse($headers)),
            ];
        });

        RateLimiter::for('auth.forgot-password', function (Request $request) {
            return Limit::perMinutes(
                self::FORGOT_PASSWORD_DECAY_MINUTES,
                self::FORGOT_PASSWORD_MAX_ATTEMPTS
            )
                ->by('forgot-password:ip:'.(string) $request->ip())
                ->response(fn (Request $request, array $headers) => self::tooManyAttemptsResponse($headers));
        });
    }

    /**
     * @param  array<string, mixed>  $headers
     */
    private static function tooManyAttemptsResponse(array $headers): \Illuminate\Http\JsonResponse
    {
        $seconds = (int) ($headers['Retry-After'] ?? 60);

        return response()->json([
            'status' => 0,
            'message' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => (int) ceil($seconds / 60),
            ]),
        ], 429, $headers);
    }
}
