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
    'redirectUrl' => 'https://mednova-seven.vercel.app/',
    'https://demoapplication.jawebhom.com/amwalpay/callback'
];
