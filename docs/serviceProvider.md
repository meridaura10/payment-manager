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
        Задається глобальний для усіх типів та операцій
        Найнищий пріорітет
        $repository->setDefaultCreator(
            callback: function (mixed $dto, array $paymentData = []) {
                return Payment::query()->firstOrCreate([
                    масив paymentData містить ключі які були задані в конфігурації
                
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
        
        Можна задавати окремо для типу чи типів та їх операцій
        Якщо задано просто тип це другий пріорітет якщо в цього типу ще є операція це найвищий пріорітет (третій)
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
        Регеструємо свої драйвери для роботи з платіжками
        Краще передавати конфіг або ж можна просто його оприділяти в самому класі
        // $paymentManager->extend('custom_gateway', function (array $config = []) {
        //     return new \App\PaymentHandlers\CustomGatewayDriver($config);
        // });
    }
}

```