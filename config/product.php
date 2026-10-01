<?php

return [
    'limit' => env('PRODUCT_LIMIT', 10),
    'default_limit_discount_amount' => env('DEFAULT_LIMIT_DISCOUNT_AMOUNT', 100000),
    'default_stock' => env('DEFAULT_STOCK', 10),
    'min_order_amount' => (int) env('PRODUCT_MIN_ORDER_AMOUNT', 50000),
];
