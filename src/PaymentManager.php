<?php

namespace Meridaura\PaymentManager;

use Closure;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Meridaura\PaymentManager\Contracts\PaymentManagerInterface;
use Meridaura\PaymentManager\Drivers\AbstractCharge;
use Meridaura\PaymentManager\Drivers\AbstractDriver;
use Meridaura\PaymentManager\Drivers\AbstractRecurring;
use Meridaura\PaymentManager\Drivers\AbstractWebhook;
use Meridaura\PaymentManager\Drivers\Contracts\SupportsChargeInterface;
use Meridaura\PaymentManager\Drivers\Contracts\SupportsRecurringInterface;
use Meridaura\PaymentManager\Drivers\Contracts\SupportsWebhookInterface;
use Meridaura\PaymentManager\Exceptions\PaymentGatewayException;
use Meridaura\PaymentManager\Support\Configurator\ConfiguratorInterface;

class PaymentManager implements PaymentManagerInterface
{
    protected array $drivers = [];

    protected array $customCreators = [];

    public function __construct(
        protected Container $container,
    ) {
    }

    public function extend(string $driver, Closure $callback): static
    {
        $this->customCreators[$driver] = $callback;

        return $this;
    }

    public function webhook(?string $driver = null, array $config = []): AbstractWebhook
    {
        $gateway = $this->driver($driver, $config);

        if (!$gateway instanceof SupportsWebhookInterface) {
            throw new PaymentGatewayException(
                sprintf('Driver [%s] does not support webhook handle.', get_class($gateway))
            );
        }

        return $gateway->webhook()->setDriver($gateway);
    }

    public function charge(?string $driver = null, array $config = []): AbstractCharge
    {
        $gateway = $this->driver($driver, $config);

        if (!$gateway instanceof SupportsChargeInterface) {
            throw new PaymentGatewayException(
                sprintf('Driver [%s] does not support one-time charges.', get_class($gateway))
            );
        }

        return $gateway->charge()->setGatewayName($gateway::getGatewayName());
    }

    public function recurring(?string $driver = null, array $config = []): AbstractRecurring
    {
        $gateway = $this->driver($driver, $config);

        if (!$gateway instanceof SupportsRecurringInterface) {
            throw new PaymentGatewayException(
                sprintf('Driver [%s] does not support recurring payments (subscriptions).', get_class($gateway))
            );
        }

        return $gateway->recurring()->setGatewayName($gateway::getGatewayName());
    }

    public function driver(?string $driver = null, array $config = []): AbstractDriver
    {
        if (!empty($config)) {
            return $this->build($driver, $config);
        }

        if (!isset($this->drivers[$driver])) {
            $this->drivers[$driver] = $this->build($driver);
        }

        return $this->drivers[$driver];
    }

    protected function build(string $driver, array $config = []): AbstractDriver
    {
        if (!isset($this->customCreators[$driver])) {
            throw new InvalidArgumentException("Payment driver [{$driver}] is not supported.");
        }

        $configurator = $this->container->make(ConfiguratorInterface::class);

        $defaultConfig = $configurator->getDriverConfig($driver);
        $mergedConfig = array_merge($defaultConfig, $config);

        return $this->customCreators[$driver]($mergedConfig);
    }

    public function __call(string $method, array $parameters): mixed
    {
        $driverName = $parameters[0] ?? null;
        $config = $parameters[1] ?? [];

        $gateway = $this->driver($driverName, $config);

        if (!method_exists($gateway, $method)) {
            throw new \BadMethodCallException(
                sprintf('Driver [%s] does not support method [%s].', get_class($gateway), $method)
            );
        }

        $handler = $gateway->$method();

        if (method_exists($handler, 'setGatewayName')) {
            $handler->setGatewayName($gateway::getGatewayName());
        }

        if (method_exists($handler, 'setDriver')) {
            $handler->setDriver($gateway);
        }

        return $handler;
    }
}