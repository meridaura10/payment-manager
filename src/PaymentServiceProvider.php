<?php

namespace Meridaura\PaymentManager;

use Illuminate\Support\ServiceProvider;
use Meridaura\PaymentManager\Contracts\PaymentManagerInterface;

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