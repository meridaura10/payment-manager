<?php

namespace Meridaura\PaymentManager;

use Closure;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Meridaura\PaymentManager\Contracts\GatewayRecurringInterface;
use Meridaura\PaymentManager\Contracts\PaymentGatewayInterface;
use Meridaura\PaymentManager\Contracts\PaymentManagerInterface;
use Meridaura\PaymentManager\Contracts\SupportsChargesInterface;
use Meridaura\PaymentManager\Contracts\SupportsRecurringInterface;
use Meridaura\PaymentManager\Contracts\SupportsWebhookInterface;
use Meridaura\PaymentManager\Drivers\AbstractCharge;
use Meridaura\PaymentManager\Drivers\AbstractWebhook;
use Meridaura\PaymentManager\Exceptions\PaymentGatewayException;
use Meridaura\PaymentManager\Models\Payment;
use Meridaura\PaymentManager\Support\Configurator\ConfiguratorInterface;
use Meridaura\PaymentManager\Support\PaymentRepository\PaymentRepositoryInterface;

class PaymentManager implements PaymentManagerInterface
{
    /**
     * Масив створених екземплярів драйверів (Singleton pattern).
     */
    protected array $drivers = [];

    /**
     * Масив кастомних замикань для створення драйверів.
     */
    protected array $customCreators = [];

    public function __construct(
        protected Container $container,
    ) {
    }

    /**
     * Реєстрація нового драйвера.
     */
    public function extend(string $driver, Closure $callback): static
    {
        $this->customCreators[$driver] = $callback;

        return $this;
    }

    public function webhooks(?string $driver = null, array $config = []): AbstractWebhook
    {
        $gateway = $this->driver($driver, $config);

        if (!$gateway instanceof SupportsWebhookInterface) {
            throw new PaymentGatewayException(
                sprintf('Driver [%s] does not support webhook handle.', get_class($gateway))
            );
        }

        return $gateway->webhooks();
    }

    /**
     * @throws PaymentGatewayException
     */
    public function charges(?string $driver = null, array $config = []): AbstractCharge
    {
        $gateway = $this->driver($driver, $config);

        if (!$gateway instanceof SupportsChargesInterface) {
            throw new PaymentGatewayException(
                sprintf('Driver [%s] does not support one-time charges.', get_class($gateway))
            );
        }

        return $gateway->charges()->setGatewayName($gateway::getGatewayName());
    }

    /**
     * @throws PaymentGatewayException
     */
    public function recurring(?string $driver = null, array $config = []): GatewayRecurringInterface
    {
        $gateway = $this->driver($driver, $config);

        if (!$gateway instanceof SupportsRecurringInterface) {
            throw new PaymentGatewayException(
                sprintf('Driver [%s] does not support recurring payments (subscriptions).', get_class($gateway))
            );
        }

        return $gateway->recurring();
    }

    public function driver(?string $driver = null, array $config = []): PaymentGatewayInterface
    {
        if (!empty($config)) {
            return $this->build($driver, $config);
        }

        if (!isset($this->drivers[$driver])) {
            $this->drivers[$driver] = $this->build($driver);
        }

        return $this->drivers[$driver];
    }

    protected function build(string $driver, array $config = []): PaymentGatewayInterface
    {
        if (!isset($this->customCreators[$driver])) {
            throw new InvalidArgumentException("Payment driver [{$driver}] is not supported.");
        }

        $configurator = $this->container->make(ConfiguratorInterface::class);

        $defaultConfig = $configurator->getDriverConfig($driver);
        $mergedConfig = array_merge($defaultConfig, $config);

        /* @var PaymentGatewayInterface $gateway */
        $gateway = $this->customCreators[$driver]($this->container, $mergedConfig);
        $gatewayConfig = $gateway->getGatewayConfig();
        $gateway->setConfig(array_merge($gatewayConfig, $mergedConfig));

        return $gateway;
    }
}