<?php

namespace Meridaura\PaymentManager\Drivers;

use Illuminate\Support\Arr;
use Meridaura\PaymentManager\Contracts\PaymentGatewayInterface;

abstract class AbstractGateway implements PaymentGatewayInterface
{
    public function __construct(
        protected array $config = []
    ) {

    }

    public function setConfig(array $config): static
    {
        $this->config = $config;

        return $this;
    }

    public function getConfig(?string $key = null, mixed $default = null): mixed
    {
        return $key ? Arr::get($this->config, $key, $default) : $this->config;
    }
}