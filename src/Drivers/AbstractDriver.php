<?php

namespace Meridaura\PaymentManager\Drivers;

use Illuminate\Support\Arr;
use Meridaura\PaymentManager\Contracts\PaymentGatewayInterface;
use Meridaura\PaymentManager\Events\PaymentMake;
use Meridaura\PaymentManager\Models\Payment;
use Meridaura\PaymentManager\Support\Configurator\ConfiguratorInterface;

abstract class AbstractDriver implements PaymentGatewayInterface
{
    protected array $config = [];

    public function setConfig(array $config): static
    {
        $this->config = $config;

        return $this;
    }

    public function getConfig(?string $key = null, mixed $default = null): mixed
    {
        return $key ? Arr::get($this->config, $key, $default) : $this->config;
    }

    abstract public static function getGatewayName(): string;
}