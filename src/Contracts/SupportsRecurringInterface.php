<?php

namespace Meridaura\PaymentManager\Contracts;

interface SupportsRecurringInterface extends PaymentGatewayInterface
{
    public function recurring(): GatewayRecurringInterface;
}