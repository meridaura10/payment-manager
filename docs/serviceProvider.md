# Налаштування Service Provider

```php
<?php

namespace App\Providers;

use App\Models\Payment;
use Illuminate\Support\ServiceProvider;
use Meridaura\PaymentManager\Contracts\PaymentManagerInterface;
use Meridaura\PaymentManager\Support\PaymentRepository\PaymentRepositoryInterface;

class PaymentManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $manager = $this->app->make(PaymentManagerInterface::class);
        $repository = $this->app->make(PaymentRepositoryInterface::class);

        $this->configurePaymentCreation($repository);
        $this->registerCustomDrivers($manager);
    }

    /**
     * Configure how payment records are created.
     */
    protected function configurePaymentCreation(PaymentRepositoryInterface $repository): void
    {
        /*
         * GLOBAL DEFAULT
         * Applies to any type and operation unless explicitly overridden below.
         */
        Задається глобально для всіх типів та операцій
        Найнижчий пріоритет
        $repository->setDefaultCreator(
            callback: function (mixed $dto, array $paymentData = []) {
                return Payment::query()->firstOrCreate([
                    Масив paymentData містить ключі, які були визначені в конфігурації
                
                    'gateway'   => $paymentData['gateway'] ?? null,
                    'type'    => $paymentData['type'] ?? null,
                    'operation' => $paymentData['operation'] ?? null,
                    'status'    => 'new', // Initial database status
                ], [
                    'amount'   => $dto->amount ?? 0,
                    'currency' => $dto->currency ?? 'UAH',
                    ...$paymentData
                ]);
            }
        );
        
        Можна визначати окремо для типу (або кількох типів) та їхніх операцій
        Якщо задано лише тип — це середній пріоритет, якщо для цього типу вказано ще й операцію — це найвищий пріоритет
        $repository->setDefaultCreator(
            callback: function (ChargePurchaseRequest $dto, array $paymentData = []) {},
            type: ['charge'],
            operation: ['purchase'],
        );
    }

    /**
     * Register custom payment gateway drivers.
     */
    protected function registerCustomDrivers(PaymentManagerInterface $paymentManager): void
    {
        Реєструємо власні драйвери для роботи з платіжними системами
        Конфіг можна передавати під час реєстрації або ж визначати безпосередньо в самому класі
        // $paymentManager->extend('custom_gateway', function (array $config = []) {
        //     return new \App\PaymentHandlers\CustomGatewayDriver($config);
        // });
    }
}
```