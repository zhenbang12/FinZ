<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Demo Mode Enabled
    |--------------------------------------------------------------------------
    |
    | Enables the link-only interactive demo access route for FinZ.
    |
    */
    'enabled' => env('DEMO_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Demo Access Token
    |--------------------------------------------------------------------------
    |
    | When set, users must provide this token via query parameter or URL path
    | (e.g. /demo?token=finz-preview-access) to access the demo.
    |
    */
    'token' => env('DEMO_ACCESS_TOKEN', 'finz-preview-access'),

    /*
    |--------------------------------------------------------------------------
    | Demo User Email
    |--------------------------------------------------------------------------
    |
    | The designated demo user account email.
    |
    */
    'user_email' => env('DEMO_USER_EMAIL', 'demo@finz.app'),
];
