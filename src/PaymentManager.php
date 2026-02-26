<?php

namespace Meridaura\PaymentManager;

use Closure;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Meridaura\PaymentManager\Contracts\GatewayChargeInterface;
use Meridaura\PaymentManager\Contracts\GatewayRecurringInterface;
use Meridaura\PaymentManager\Contracts\PaymentGatewayInterface;
use Meridaura\PaymentManager\Contracts\PaymentManagerInterface;
use Meridaura\PaymentManager\Contracts\SupportsChargesInterface;
use Meridaura\PaymentManager\Contracts\SupportsRecurringInterface;
use Meridaura\PaymentManager\Exceptions\PaymentGatewayException;

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

    /**
     * @param Container $container Laravel IoC Container
     */
    public function __construct(protected Container $container)
    {
    }

    public function getDefaultDriver(): string
    {
        return $this->container->make('config')->get('payment.default', 'monobank');
    }

    /**
     * Реєстрація нового драйвера.
     */
    public function extend(string $driver, Closure $callback): static
    {
        $this->customCreators[$driver] = $callback;

        return $this;
    }

    /**
     * @throws PaymentGatewayException
     */
    public function charges(?string $driver = null, array $config = []): GatewayChargeInterface
    {
        $gateway = $this->driver($driver, $config);

        if (!$gateway instanceof SupportsChargesInterface) {
            throw new PaymentGatewayException(
                sprintf('Driver [%s] does not support one-time charges.', get_class($gateway))
            );
        }

        return $gateway->charges();
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
        $driver ??= $this->getDefaultDriver();

        if (!empty($config)) {
            return $this->build($driver, $config);
        }

        if (!isset($this->drivers[$driver])) {
            $this->drivers[$driver] = $this->build($driver);
        }

        return $this->drivers[$driver];
    }

    public function build(string $driver, array $config = []): PaymentGatewayInterface
    {
        if (!isset($this->customCreators[$driver])) {
            throw new InvalidArgumentException("Payment driver [{$driver}] is not supported.");
        }

        /* @var PaymentGatewayInterface $gateway */
        $gateway = $this->customCreators[$driver]($this->container, $config);

        if ($config) {
            $gateway->setConfig(array_merge($gateway->getConfig(), $config));
        }

        return $gateway;
    }
}