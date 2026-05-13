<?php

use Meridaura\PaymentManager\Enums\PaymentEventEnum;
use Meridaura\PaymentManager\Enums\PaymentTypeEnum;
use \Meridaura\PaymentManager\Enums\PaymentStageEnum;

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
            'state'     =>  'status',
            'response'   => 'added->response',
            'webhook_data' => 'added->notify',
            'webhook_modify_at' => 'webhook_modify_at',
        ],

        /*
        |--------------------------------------------------------------------------
        | Payment Statuses Mapping
        |--------------------------------------------------------------------------
        */
        'statuses' => [
            PaymentTypeEnum::MANUAL->name => [
                PaymentStageEnum::CREATED->name  => 'new',       // Створили
                PaymentStageEnum::PENDING->name  => 'pending',   // Є лінк, чекаємо
                PaymentStageEnum::PAID->name     => 'paid',      // Гроші зайшли
                PaymentStageEnum::FAILED->name   => 'error',     // Помилка API або відмова банку
                PaymentStageEnum::CANCELED->name => 'expired',   // Лінк протерміновано
            ],
            PaymentTypeEnum::RECURRING->name => [
                PaymentStageEnum::CREATED->name  => 'new',       // Створили
                PaymentStageEnum::PENDING->name  => 'pending',   // Є лінк, чекаємо
                PaymentStageEnum::PAID->name     => 'paid',      // Гроші зайшли
                PaymentStageEnum::FAILED->name   => 'error',     // Помилка API або відмова банку
                PaymentStageEnum::CANCELED->name => 'expired',   // Лінк протерміновано
            ]
        ],

        // Values to save in the 'method' column depending on the transaction type
        'type_values' => [
            PaymentTypeEnum::MANUAL->name => 'manual',
            PaymentTypeEnum::RECURRING->name => 'recurring',
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

        'save_quietly' => false,
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
                'reuse_links'   => false, // Set to false to disable ONLY for Monobank
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
        PaymentEventEnum::STATUS_CHANGED->name => null,

        PaymentTypeEnum::MANUAL->name => [
            PaymentStageEnum::CREATED->name  => null,       // Створили
            PaymentStageEnum::PENDING->name  => null,   // Є лінк, чекаємо
            PaymentStageEnum::PAID->name     => null,      // Гроші зайшли
            PaymentStageEnum::FAILED->name   => null,     // Помилка API або відмова банку
            PaymentStageEnum::CANCELED->name => null,   // Лінк протерміновано
            PaymentEventEnum::STATUS_CHANGED->name => null,
        ],
        PaymentTypeEnum::RECURRING->name => [
            PaymentStageEnum::CREATED->name  => null,       // Створили
            PaymentStageEnum::PENDING->name  => null,   // Є лінк, чекаємо
            PaymentStageEnum::PAID->name     => null,      // Гроші зайшли
            PaymentStageEnum::FAILED->name   => null,     // Помилка API або відмова банку
            PaymentStageEnum::CANCELED->name => null,   // Лінк протерміновано
            PaymentEventEnum::STATUS_CHANGED->name => null,
        ],
    ],
];