<?php

return [
    'limit' => env('PRODUCT_LIMIT', 10),
    'default_limit_delivery_amount' => env('DEFAULT_LIMIT_DELIVERY_AMOUNT', 2000000),
    'default_delivery_amount' => env('DEFAULT_DELIVERY_AMOUNT', 200000),
    'default_limit_discount_amount' => env('DEFAULT_LIMIT_DISCOUNT_AMOUNT', 100000),
];