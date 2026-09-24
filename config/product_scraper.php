<?php

return [
    'url' => env('PRODUCT_SCRAPER_URL', 'http://127.0.0.1:8001'),
    'token' => env('PRODUCT_SCRAPER_TOKEN'),
    'timeout' => (int) env('PRODUCT_SCRAPER_TIMEOUT', 180),

    /*
    |--------------------------------------------------------------------------
    | Stale product refresh
    |--------------------------------------------------------------------------
    |
    | RefreshStaleProductsJob picks active completed products whose
    | updated_at is older than "hours" and refreshes them via /apply (code).
    |
    */
    'stale_refresh' => [
        'enabled' => filter_var(
            env('PRODUCT_SCRAPER_STALE_REFRESH_ENABLED', true),
            FILTER_VALIDATE_BOOLEAN,
        ),
        'limit' => (int) env('PRODUCT_SCRAPER_STALE_LIMIT', 10),
        'hours' => (int) env('PRODUCT_SCRAPER_STALE_HOURS', 48),
        // Pause between /apply calls so upstream sites are not hit back-to-back.
        'delay_seconds' => (int) env('PRODUCT_SCRAPER_STALE_DELAY_SECONDS', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cart-triggered product refresh
    |--------------------------------------------------------------------------
    |
    | When a product is added to the cart, eligible stale products are
    | dispatched to this Horizon queue (processed before "default").
    |
    */
    'cart_refresh' => [
        'queue' => env('PRODUCT_SCRAPER_CART_REFRESH_QUEUE', 'high'),
    ],
];
