<?php

namespace Meridaura\PaymentManager;

use Illuminate\Support\ServiceProvider;
use Meridaura\PaymentManager\Contracts\PaymentManagerInterface;
use Meridaura\PaymentManager\Support\Configurator\Configurator;
use Meridaura\PaymentManager\Support\Configurator\ConfiguratorInterface;
use Meridaura\PaymentManager\Support\PaymentRepository\PaymentRepository;
use Meridaura\PaymentManager\Support\PaymentRepository\PaymentRepositoryInterface;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentManagerInterface::class, PaymentManager::class);
        $this->app->singleton(ConfiguratorInterface::class, Configurator::class);
        $this->app->singleton(PaymentRepositoryInterface::class, PaymentRepository::class);

        $this->publishLocal();
    }

    public function boot(): void
    {
        $this->publishConsole();
    }

    private function publishLocal(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . './../config/payment-manager.php', 'payment'
        );
    }

    private function publishConsole(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Meridaura\PaymentManager\Console\Commands\InstallCommand::class,
            ]);

            $this->publishes([
                __DIR__ . '/../config/payment-manager.php' => config_path('payment-manager.php'),
            ], 'payment-config');

            $this->publishes([
                __DIR__ . '/../stubs/PaymentManagerServiceProvider.stub' => app_path('Providers/PaymentManagerServiceProvider.php'),
            ], 'payment-provider');
        }
    }
}