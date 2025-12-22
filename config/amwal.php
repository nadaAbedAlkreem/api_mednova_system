<?php

return [
    'environment' => env('AMWAL_ENV', 'test'), // test / live
    'mid' => env('AMWAL_MID', ''),
    'tid' => env('AMWAL_TID', ''),
    'secure_hash' => env('AMWAL_SECURE_HASH', ''),
    'base_url' => env('AMWAL_BASE_URL', 'https://test.amwalpg.com'), // حسب environment
];
