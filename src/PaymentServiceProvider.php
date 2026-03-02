<?php

namespace Meridaura\PaymentManager;

use Illuminate\Support\ServiceProvider;
use Meridaura\PaymentManager\Contracts\PaymentManagerInterface;
use Meridaura\PaymentManager\Enums\PaymentDriverEnum;
use Meridaura\PaymentManager\Drivers\Monobank\MonobankDriver;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentManagerInterface::class, PaymentManager::class);
        $this->app->alias(PaymentManagerInterface::class, 'paymentManager');

        $this->publishLocal();
    }

    public function boot(): void
    {
        $manager = $this->app->make('paymentManager');

        $manager->extend(PaymentDriverEnum::MONOBANK->value, function () {
            return new MonobankDriver(config('payment.drivers.monobank'));
        });

        $manager->extend(PaymentDriverEnum::NOVAPAY->value, function ($app) {
            return new \Meridaura\PaymentManager\Drivers\NovaPay\NovaPayDriver(config('payment.drivers.novapay'));
        });

        $this->publishConsole();
    }

    private function publishLocal(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . './../config/payment.php', 'payment'
        );
    }

    private function publishConsole(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/payment.php' => config_path('payment.php'),
            ], 'payment-config');
        }
    }
}