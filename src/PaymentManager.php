<?php

namespace Meridaura\PaymentManager;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Str;
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

        // Якщо передали кастомний конфіг - завжди будуємо свіжий об'єкт
        if (!empty($config)) {
            return $this->build($driver, $config);
        }

        // Інакше беремо з кешу або створюємо
        if (!isset($this->drivers[$driver])) {
            $this->drivers[$driver] = $this->build($driver);
        }

        return $this->drivers[$driver];
    }

    public function build(string $driver, array $config = []): PaymentGatewayInterface
    {
        // 1. Спочатку шукаємо серед зареєстрованих через extend()
        if (isset($this->customCreators[$driver])) {
            return $this->customCreators[$driver]($this->container, $config);
        }

        // 2. Якщо є метод всередині самого класу (наприклад createMonobankDriver)
        $method = 'create' . Str::studly($driver) . 'Driver';

        if (method_exists($this, $method)) {
            return $this->$method($config);
        }

        throw new InvalidArgumentException("Payment driver [{$driver}] is not supported.");
    }
}