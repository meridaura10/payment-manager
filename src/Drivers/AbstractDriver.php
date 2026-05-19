<?php

namespace Meridaura\PaymentManager\Drivers;

use Illuminate\Support\Arr;
use Meridaura\PaymentManager\Drivers\Contracts\SupportsChargeInterface;
use Meridaura\PaymentManager\Drivers\Contracts\SupportsRecurringInterface;
use Meridaura\PaymentManager\Enums\PaymentTypeEnum;

abstract class AbstractDriver
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

    public function getTypeClass(\UnitEnum|string $type): mixed
    {
        if ($type instanceof PaymentTypeEnum) {
            return match ($type) {
                PaymentTypeEnum::CHARGE => $this instanceof SupportsChargeInterface ? $this->charge() : null,
                PaymentTypeEnum::RECURRING => $this instanceof SupportsRecurringInterface ? $this->recurring() : null,
                default => null,
            };
        }

        return $this->getCustomTypeClass($type);
    }

    public function getCustomTypeClass(\UnitEnum|string $type): mixed
    {
        return null;
    }

    abstract public static function getGatewayName(): string;
}