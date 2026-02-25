<?php

namespace Meridaura\PaymentManager\Contracts;

interface PaymentGatewayInterface
{
    public function setConfig(array $config): static;

    public function getConfig(?string $key, mixed $default = null): array;

    public function charges(): GatewayChargeInterface;
}