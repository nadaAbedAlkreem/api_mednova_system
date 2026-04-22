<?php

return [
    'environment' => env('AMWAL_ENV', 'test'),

    'mid' => env('AMWAL_MID'),
    'tid' => env('AMWAL_TID'),
    'secure_key' => env('AMWAL_SECURE_HASH'),

    'base_url' => env('AMWAL_BASE_URL', 'https://test.amwalpg.com:14443'),

    'payment_methods' => [
        'card' => 1,
    ],

    'currency' => [
        'OMR' => 512,
    ],
    'redirectUrl' => 'https://mednovacare.com/',

    'currency_en' => 'OMR',

    'gateway_fees' => [
        'domestic'      => 0.009, // 0.9%
        'international' => 0.018, // 1.8%
    ],

    'platform_commission' => [
        'default_rate' => 0.10, // 10%
    ],
 ];
