<?php

namespace Meridaura\PaymentManager\Contracts;

interface SupportsChargesInterface extends PaymentGatewayInterface
{
    public function charges(): GatewayChargeInterface;
}