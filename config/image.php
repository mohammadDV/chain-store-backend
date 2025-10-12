<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Image Driver
    |--------------------------------------------------------------------------
    |
    | Intervention Image supports "GD Library" and "Imagick" to process images
    | internally. You may choose one of them according to your PHP
    | configuration. By default PHP's "GD Library" implementation is used.
    |
    | Supported: "gd", "imagick"
    |
    */

    'driver' => 'gd',

    //index size
    'index-image-sizes' => [
        'large' => [
            'width' => 800,
            'height' => 450
        ],
        'medium' => [
            'width' => 400,
            'height' => 300
        ],
        'small' => [
            'width' => 80,
            'height' => 60
        ],

    ],

    'default-current-index-image' => 'medium',
    // 'default-profile-image' => 'https://prod-data-sport.storage.iran.liara.space/oshtow/default-avatar.jpg',
    'default-profile-image' => 'default/default-avatar.jpg',
    // 'default-background-image' => 'https://prod-data-sport.storage.iran.liara.space/oshtow/default-user-banner.jpg',
    'default-background-image' => 'default/default-user-banner.jpg',
    // 'default_business_image' => 'https://prod-data-sport.storage.iran.liara.space/oshtow/default-destination1.jpeg',
    'default_business_image' => 'default/default-destination.jpeg',
];
