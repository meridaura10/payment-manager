<?php

use Meridaura\PaymentManager\Enums\PaymentEventEnum;
use Meridaura\PaymentManager\Enums\PaymentOperationEnum;
use Meridaura\PaymentManager\Enums\PaymentTypeEnum;
use Meridaura\PaymentManager\Enums\PaymentStageEnum;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payment Model
    |--------------------------------------------------------------------------
    */
    'model' => \Meridaura\PaymentManager\Models\Payment::class,

    /*
    |--------------------------------------------------------------------------
    | Database Schema Mapping
    |--------------------------------------------------------------------------
    | Define how internal package concepts map to your database columns.
    */
    'database' => [
        'columns' => [
            'gateway' => 'gateway',
            'type' => 'type',
            'operation' => 'operation',
            'extern_id' => 'extern_id',
            'page_url' => 'page_url',
            'expires_at' => 'expires_at',
            'state' => 'status',
            'response' => 'added->response',
            'webhook_data' => 'added->notify',
            'webhook_modify_at' => 'webhook_modify_at',
        ],

        'type_values' => [
            PaymentTypeEnum::CHARGE->name => 'charge',
            PaymentTypeEnum::RECURRING->name => 'recurring',
        ],

        'operation_values' => [
            PaymentOperationEnum::CHARGE_PURCHASE->name => 'charge_purchase',
            PaymentOperationEnum::RECURRING_SETUP->name => 'recurring_setup',
            PaymentOperationEnum::RECURRING_EXECUTE->name => 'recurring_execute',
        ],

        /*
         * Cascading Statuses Mapping
         * Hierarchy: Type -> Operation. Operations override Type defaults.
         */
        'statuses' => [
            // 1. CHARGE DEFAULTS
            PaymentTypeEnum::CHARGE->name => [
                PaymentStageEnum::CREATED->name => 'new',
                PaymentStageEnum::PENDING->name => 'pending',
                PaymentStageEnum::PAID->name => 'paid',
                PaymentStageEnum::FAILED->name => 'error',

                // Specific operation overrides
                PaymentOperationEnum::CHARGE_PURCHASE->name => [
                    // PaymentStageEnum::PAID->name => 'purchased',
                ]
            ],

            // 2. RECURRING DEFAULTS
            PaymentTypeEnum::RECURRING->name => [
                PaymentStageEnum::CREATED->name => 'new',
                PaymentStageEnum::PENDING->name => 'pending',
                PaymentStageEnum::PAID->name => 'paid',
                PaymentStageEnum::FAILED->name => 'error',

                // Override for saving card
                PaymentOperationEnum::RECURRING_SETUP->name => [
                    PaymentStageEnum::PAID->name => 'subscribed',
                ],

                // Override for auto-charging
                PaymentOperationEnum::RECURRING_EXECUTE->name => [
                    PaymentStageEnum::PAID->name => 'renewed',
                    PaymentStageEnum::FAILED->name => 'failed_renewal',
                ]
            ]
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Features
    |--------------------------------------------------------------------------
    */
    'features' => [
        'link_lifetime' => 3600, // Global link expiration in seconds
        'save_quietly' => false, // Save models without triggering Eloquent events
    ],

    /*
    |--------------------------------------------------------------------------
    | Gateways (Drivers) Configuration
    |--------------------------------------------------------------------------
    | Cascading configuration: Root -> Type -> Operation.
    */
    'drivers' => [
        'monobank' => [
            'base_url' => env('MONOBANK_BASE_URL', 'https://api.monobank.ua'),
            'token' => env('MONOBANK_TOKEN', ''),

            'features' => [
                'link_lifetime' => null,
            ],

            PaymentTypeEnum::CHARGE->name => [
                PaymentOperationEnum::CHARGE_PURCHASE->name => [
                    'features' => [
                        'link_lifetime' => 86400, // 24 hours
                    ]
                ]
            ],

            PaymentTypeEnum::RECURRING->name => [
                PaymentOperationEnum::RECURRING_SETUP->name => [
                    'features' => [
                        'link_lifetime' => 3600, // 1 hour
                    ]
                ],
                PaymentOperationEnum::RECURRING_EXECUTE->name => [
                    'features' => [
                        'link_lifetime' => 30,
                    ]
                ]
            ]
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Mapping
    |--------------------------------------------------------------------------
    | Map your application events to package lifecycle stages.
    | Cascading hierarchy: Global -> Type -> Operation.
    */
    'events' => [
        PaymentEventEnum::STATUS_CHANGED->name => null,

        PaymentTypeEnum::CHARGE->name => [
            PaymentEventEnum::STATUS_CHANGED->name => null,

            PaymentOperationEnum::CHARGE_PURCHASE->name => [
                PaymentStageEnum::CREATED->name => null,
                PaymentStageEnum::PENDING->name => null,
                PaymentStageEnum::PAID->name => null,
                PaymentStageEnum::FAILED->name => null,
                PaymentEventEnum::STATUS_CHANGED->name => null,
            ],
        ],

        PaymentTypeEnum::RECURRING->name => [
            PaymentEventEnum::STATUS_CHANGED->name => null,

            PaymentOperationEnum::RECURRING_SETUP->name => [
                PaymentStageEnum::CREATED->name => null,
                PaymentStageEnum::PENDING->name => null,
                PaymentStageEnum::PAID->name => null,
                PaymentStageEnum::FAILED->name => null,
                PaymentEventEnum::STATUS_CHANGED->name => null,
            ],

            PaymentOperationEnum::RECURRING_EXECUTE->name => [
                PaymentStageEnum::CREATED->name => null,
                PaymentStageEnum::PENDING->name => null,
                PaymentStageEnum::PAID->name => null,
                PaymentStageEnum::FAILED->name => null,
                PaymentEventEnum::STATUS_CHANGED->name => null,
            ],
        ],
    ],
];