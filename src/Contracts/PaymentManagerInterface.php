<?php

namespace Meridaura\PaymentManager\Contracts;

interface PaymentManagerInterface
{
    public function build(string $driver, array $config): PaymentGatewayInterface;
}