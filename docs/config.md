# Налаштування конфігу
```php
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
    
    // Можливість визначити кожну колонку під вашу БД і міграцію.
    // Підтримується для кожного поля (окрім часових) JSON формат, як в прикладі з response|webhook_data.
    'database' => [
        'columns' => [
            // Що за платіжна система
            'gateway' => 'gateway',
            
            // Тип платежу (наприклад, в системі одразу є тип recurring|charge)
            'type' => 'type',
            
            // Операція платежу (наприклад, для рекурентних платежів: перший на підготовку платежу, другий для автозняття)
            'operation' => 'operation',
            
            // Зовнішня айдішка від платіжних систем
            'extern_id' => 'extern_id',
            
            // Посилання на оплату
            'page_url' => 'page_url',
            
            // До якого часу посилання дійсне (буде регулюватися, якщо ставити link_lifetime)
            'expires_at' => 'expires_at',
            
            // На якому етапі платіж
            'state' => 'status',
            
            // Відповідь від платіжної системи
            'response' => 'added->response',
            
            // Те, що прийшло на вебхук
            'webhook_data' => 'added->notify',
            
            // Час останньої модифікації вебхуком.
            // Використовуйте це для того, щоб старіші вебхуки про створення не перебили новіші про оплату.
            'webhook_modify_at' => 'webhook_modify_at',
        ],

        // Усі типи платежів в системі (можна додавати свої)
        'type_values' => [
            // Разові оплати (стандартні)
            PaymentTypeEnum::CHARGE->name => 'manual', 
            
            // Рекурентні оплати
            PaymentTypeEnum::RECURRING->name => 'recurring', 
            
            // Приклад додавання нового типу: можна строкою, можна Enum як вище
            // 'subscription',
        ],

        // Усі типи операцій
        'operation_values' => [
            // Звичайна покупка (усі для charge)
            PaymentOperationEnum::CHARGE_PURCHASE->name => 'purchase',
            
            // Рекурентна операція SETUP: підготовка (користувач вносить свої дані, робить оплату, ми отримуємо токен)
            PaymentOperationEnum::RECURRING_SETUP->name => 'setup',
            
            // Рекурентна операція EXECUTE: виконання (сервер стягує кошти без участі користувача, використовуючи токен)
            PaymentOperationEnum::RECURRING_EXECUTE->name => 'execute',
        ],

        /*
         * Cascading Statuses Mapping
         * Hierarchy: Type -> Operation. Operations override Type defaults.
         */
        'statuses' => [
            // Який stage записувати в поле, визначене в columns.
            // На кожен етап життєвого шляху можна визначити свій stage|status, його буде зберігати в базу даних.
            
            // Найнижчий пріоритет - глобальні налаштування
            PaymentStageEnum::CREATED->name => 'new',
            PaymentStageEnum::PENDING->name => 'pending',
            PaymentStageEnum::PAID->name => 'paid',
            PaymentStageEnum::FAILED->name => 'error',
            PaymentStageEnum::CANCELED->name => 'expired',
            
            // 1. CHARGE DEFAULTS
            // Вищий пріоритет - вказуємо для конкретного типу
            PaymentTypeEnum::CHARGE->name => [
                PaymentStageEnum::CREATED->name => 'new',
                PaymentStageEnum::PENDING->name => 'pending',
                PaymentStageEnum::PAID->name => 'paid',
                PaymentStageEnum::FAILED->name => 'error',
                PaymentStageEnum::CANCELED->name => 'expired',

                // Specific operation overrides
                // Найвищий пріоритет - вказуємо для конкретної операції
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
                PaymentStageEnum::CANCELED->name => 'expired',

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
        // Найнижчий пріоритет: скільки часу живе посилання на оплату (якраз для поля expires_at)
        'link_lifetime' => 3600, // Global link expiration in seconds
        
        // Чи тихо оновляти модель (save/saveQuietly)
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
            // Загальні налаштування (найнижчий пріоритет).
            // Можна буде передавати напряму або визначати прямо в драйвері.
            'base_url' => env('MONOBANK_BASE_URL', 'https://api.monobank.ua'),
            'token' => env('MONOBANK_TOKEN', ''),

            'features' => [
                // Найнижчий пріоритет (вимкнено).
                // Якщо цього поля взагалі немає, то йде до пріоритету вище.
                'link_lifetime' => null,
            ],

            // Вищий пріоритет (шукає по типу)
            PaymentTypeEnum::CHARGE->name => [
                // Найвищий пріоритет (шукає по типу та операції)
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
    
    // На усьому життєвому шляху платіж посилає евенти, які можна визначити тут.
    // Можна задати глобально для усіх типів по ключу PaymentStageEnum, значення - свій евент.
    'events' => [
        // Найнижчий (глобальний) пріоритет
        PaymentEventEnum::STATUS_CHANGED->name => null,

        // Вищий пріоритет - по типу
        PaymentTypeEnum::CHARGE->name => [
            PaymentEventEnum::STATUS_CHANGED->name => null,

            // Найвищий пріоритет - по типу операції
            PaymentOperationEnum::CHARGE_PURCHASE->name => [
                PaymentStageEnum::CREATED->name => null,
                PaymentStageEnum::PENDING->name => null,
                PaymentStageEnum::PAID->name => null,
                PaymentStageEnum::FAILED->name => null,
                PaymentStageEnum::CANCELED->name => null,
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
                PaymentStageEnum::CANCELED->name => null,
                PaymentEventEnum::STATUS_CHANGED->name => null,
            ],

            PaymentOperationEnum::RECURRING_EXECUTE->name => [
                PaymentStageEnum::CREATED->name => null,
                PaymentStageEnum::PENDING->name => null,
                PaymentStageEnum::PAID->name => null,
                PaymentStageEnum::FAILED->name => null,
                PaymentStageEnum::CANCELED->name => null,
                PaymentEventEnum::STATUS_CHANGED->name => null,
            ],
        ],
    ],
];
```