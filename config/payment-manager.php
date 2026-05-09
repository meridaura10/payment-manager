<?php

use Meridaura\PaymentManager\Enums\PaymentTypeEnums;
use \Meridaura\PaymentManager\Enums\PaymentStateEnums;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payment Model
    |--------------------------------------------------------------------------
    | Here you can specify the Eloquent model that should be used to retrieve
    | and store payment information. It must implement the required interface.
    */
    'model' => \Meridaura\PaymentManager\Models\Payment::class,

    /*
    |--------------------------------------------------------------------------
    | Database Schema Mapping
    |--------------------------------------------------------------------------
    | This package is blind to your database schema. Here you can map the
    | internal package data to your specific database columns and values.
    */
    'database' => [
        // Column names in your payments table
        'columns' => [
            'gateway'    => 'gateway',
            'type'       => 'type',
            'extern_id'  => 'extern_id',
            'page_url'   => 'page_url',
            'expires_at' => 'expires_at',
            'status'     => 'status',
            'webhook_data' => 'added->notify',
            'webhook_modify_at' => 'webhook_modify_at',
        ],

        /*
        |--------------------------------------------------------------------------
        | Payment Statuses Mapping
        |--------------------------------------------------------------------------
        */
        'statuses' => [
            PaymentTypeEnums::MANUAL->value => [
                PaymentStateEnums::CREATED->value  => 'new',       // Створили
                PaymentStateEnums::PENDING->value  => 'pending',   // Є лінк, чекаємо
                PaymentStateEnums::PAID->value     => 'paid',      // Гроші зайшли
                PaymentStateEnums::FAILED->value   => 'error',     // Помилка API або відмова банку
                PaymentStateEnums::CANCELED->value => 'expired',   // Лінк протерміновано
            ],
            PaymentTypeEnums::RECURRING->value => [
                PaymentStateEnums::CREATED->value  => 'new',       // Створили
                PaymentStateEnums::PENDING->value  => 'pending',   // Є лінк, чекаємо
                PaymentStateEnums::PAID->value     => 'paid',      // Гроші зайшли
                PaymentStateEnums::FAILED->value   => 'error',     // Помилка API або відмова банку
                PaymentStateEnums::CANCELED->value => 'expired',   // Лінк протерміновано
            ]
        ],

        // Values to save in the 'method' column depending on the transaction type
        'type_values' => [
            PaymentTypeEnums::MANUAL->value => 'manual',
            PaymentTypeEnums::RECURRING->value => 'recurring',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Features
    |--------------------------------------------------------------------------
    | These features apply globally to all payment drivers. You can override
    | them individually inside the specific driver configuration below.
    */
    'features' => [
        // Idempotency: If true, the package will attempt to reuse an existing,
        // non-expired checkout link instead of creating a new one on the gateway.
        'reuse_links' => true,

        // The default lifetime of a checkout link in seconds (e.g., 3600 = 1 hour).
        // Used to calculate the 'expires_at' timestamp for the database and gateway.
        'link_lifetime' => 3600,
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways (Drivers) Configuration
    |--------------------------------------------------------------------------
    | Here you configure the credentials and specific behavior for each payment
    | provider. You can override global features per-driver here.
    */
    'drivers' => [
        'monobank' => [
            'base_url' => env('MONOBANK_BASE_URL', 'https://api.monobank.ua'),
            'token'    => env('MONOBANK_TOKEN', ''),

            // Driver-specific features. Set to null to fallback to global features.
            'features' => [
                'reuse_links'   => null, // Set to false to disable ONLY for Monobank
                'link_lifetime' => null, // Override the global checkout link lifetime
            ]
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Package Events
    |--------------------------------------------------------------------------
    | Map package events to your own application's event classes.
    */
    'events' => [
//        PaymentStateEnums::CREATED->value  => null,       // Створили
//        PaymentStateEnums::PENDING->value  => 'pending',   // Є лінк, чекаємо
//        PaymentStateEnums::PAID->value     => 'paid',      // Гроші зайшли
//        PaymentStateEnums::FAILED->value   => 'error',     // Помилка API або відмова банку
//        PaymentStateEnums::CANCELED->value => 'expired',
    ],
];