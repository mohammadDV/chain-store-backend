<?php

return [
    'url' => env('PRODUCT_SCRAPER_URL', 'http://127.0.0.1:8001'),
    'token' => env('PRODUCT_SCRAPER_TOKEN'),
    'timeout' => (int) env('PRODUCT_SCRAPER_TIMEOUT', 180),
];
