<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Payment Driver
    |--------------------------------------------------------------------------
    */
    'default' => env('PAYMENT_DEFAULT_DRIVER', 'monobank'),

    /*
    |--------------------------------------------------------------------------
    | Payment Drivers Configuration
    |--------------------------------------------------------------------------
    */
    'drivers' => [
        'monobank' => [
            'base_url' => env('MONOBANK_BASE_URL', 'https://api.monobank.ua/api/'),
            'token'    => env('MONOBANK_TOKEN'),
        ],

        'novapay' => [
            'base_url' => env('NOVAPAY_BASE_URL', 'https://api-qecom.novapay.ua/v1/'),
            'merchant_id' => env('NOVAPAY_MERCHANT_ID'),
            'private_key' => env('NOVAPAY_PRIVATE_KEY'),
            'public_key' => env('NOVAPAY_PUBLIC_KEY'),
        ]
    ],
];