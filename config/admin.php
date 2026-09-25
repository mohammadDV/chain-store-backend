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

];
