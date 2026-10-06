<?php

namespace Core\Http\RateLimiting;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

final class CatalogRateLimiter
{
    public const SEARCH_PER_MINUTE = 60;

    public const SITEMAP_PER_MINUTE = 10;

    public static function configure(): void
    {
        RateLimiter::for('catalog.search', function (Request $request) {
            return Limit::perMinute(self::SEARCH_PER_MINUTE)
                ->by('catalog-search:'.(string) $request->ip())
                ->response(fn (Request $request, array $headers) => self::tooManyAttemptsResponse($headers));
        });

        RateLimiter::for('catalog.sitemap', function (Request $request) {
            return Limit::perMinute(self::SITEMAP_PER_MINUTE)
                ->by('catalog-sitemap:'.(string) $request->ip())
                ->response(fn (Request $request, array $headers) => self::tooManyAttemptsResponse($headers));
        });
    }

    /**
     * @param  array<string, mixed>  $headers
     */
    private static function tooManyAttemptsResponse(array $headers): JsonResponse
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
