<?php

namespace Meridaura\PaymentManager\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\ServiceProvider;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment-manager:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the Payment Manager package (publishes config and ServiceProvider)';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->components->info('Installing Payment Manager...');

        // 1. Publish config
        $this->call('vendor:publish', ['--tag' => 'payment-config']);

        // 2. Publish ServiceProvider
        $this->call('vendor:publish', ['--tag' => 'payment-provider']);

        // 3. Register the ServiceProvider automatically (Laravel 11+)
        $this->registerServiceProvider();

        $this->components->info('Payment Manager installed successfully!');
    }

    /**
     * Register the published ServiceProvider in the application.
     */
    protected function registerServiceProvider(): void
    {
        $namespace = 'App\\Providers\\PaymentManagerServiceProvider';

        if (method_exists(ServiceProvider::class, 'addProviderToBootstrapFile')) {
            ServiceProvider::addProviderToBootstrapFile($namespace);
            $this->components->info("ServiceProvider [$namespace] successfully added to bootstrap/providers.php");
        } else {
            $this->components->warn("You are using an older version of Laravel. Please manually add $namespace to the providers array in your config/app.php file.");
        }
    }
}