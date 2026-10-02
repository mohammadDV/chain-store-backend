<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Super admin emails
    |--------------------------------------------------------------------------
    |
    | Users with these emails bypass all Filament permission and brand checks.
    |
    */

    'super_admin_emails' => array_values(array_filter(array_map(
        'strtolower',
        array_map('trim', explode(',', (string) env('ADMIN_SUPER_ADMIN_EMAILS', 'admin@gmail.com')))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Admin panel login hardening
    |--------------------------------------------------------------------------
    |
    | Filament's default is 5 attempts / 60 seconds. Admin login uses a
    | stricter window.
    |
    */

    'login' => [
        'max_attempts' => (int) env('ADMIN_LOGIN_MAX_ATTEMPTS', 3),
        'decay_seconds' => (int) env('ADMIN_LOGIN_DECAY_SECONDS', 900),
    ],

];
